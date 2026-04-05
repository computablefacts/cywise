<?php

namespace App\Providers;

use Baril\Sqlout\Engine as Engine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;
use StopWords\StopWords;
use Wamania\Snowball\StemmerFactory;

class SqloutEngine extends Engine
{
    /**
     * Apply the filters to the indexed content or search terms, tokenize it
     * and stem the words.
     *
     * @param string $content
     * @return string
     */
    protected function processString($content)
    {
        if (Str::startsWith($content, ':')) {
            return parent::processString(Str::substr($content, 1));
        }

        $lang = Str::substr($content, 0, 2);
        $contentOriginal = Str::substr($content, 3);
        $content = $contentOriginal;

        // Apply custom filters:
        foreach (config('scout.sqlout.filters', []) as $filter) {
            if (is_callable($filter)) {
                $content = call_user_func($filter, $content);
            }
        }

        // Remove stopwords:
        try {
            $stopwords = new StopWords($lang);
            $content = $stopwords->clean($content);
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
            return parent::processString($contentOriginal);
        }

        // Tokenize:
        $words = preg_split(config('scout.sqlout.token_delimiter', '/[\s]+/'), $content);

        // Remove short words:
        $minLength = config('scout.sqlout.minimum_length', 0);
        $words = collect($words)->reject(fn($word) => mb_strlen($word) < $minLength)->all();

        // Stem:
        try {
            $stemmer = StemmerFactory::create($lang);
            foreach ($words as $k => $word) {
                $words[$k] = $stemmer->stem($word);
            }
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
            return parent::processString($contentOriginal);
        }

        // Return result:
        return implode(' ', $words);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param \Laravel\Scout\Builder $builder
     * @param array $options
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        // Use FULLTEXT index
        $results = parent::performSearch($builder, $options);

        if ($results['nbHits'] > 0) {
            return $results;
        }

        // Fallback to LIKE search
        $terms = implode('%', explode(' ', $this->processString($builder->query)));

        // Creating search query:
        $query = $this->newSearchQuery($builder->model)
            ->with('record')
            ->where('record_type', $builder->model->getMorphClass())
            ->whereRaw("content LIKE '%{$terms}%'")
            ->groupBy('record_type')
            ->groupBy('record_id')
            ->selectRaw("sum(weight) as _score")
            ->addSelect(['record_type', 'record_id']);

        // Order clauses:
        if (!$builder->orders) {
            $builder->orderByScore();
        }
        if ($builder->orders) {
            foreach ($builder->orders as $i => $order) {
                if ($order['column'] == '_score') {
                    $query->orderBy($order['column'], $order['direction']);
                    continue;
                }
                $alias = 'sqlout_reserved_order_' . $i;
                $subQuery = $builder->model->newQuery()
                    ->select([
                        $builder->model->getKeyName() . " as {$alias}_id",
                        $order['column'] . " as {$alias}_order",
                    ]);
                $query->joinSub($subQuery, $alias, function ($join) use ($alias) {
                    $join->on('record_id', '=', $alias . '_id');
                });
                $query->orderBy($alias . '_order', $order['direction']);
            }
        }

        $query->whereHasMorph('record', get_class($builder->model), function ($query) use ($builder) {
            $this->applyQueryScopes($builder, $query);
        });

        // Applying limit/offset:
        if ($options['hitsPerPage'] ?? null) {
            $query->limit($options['hitsPerPage']);
            if ($options['page'] ?? null) {
                $offset = $options['page'] * $options['hitsPerPage'];
                $query->offset($offset);
            }
        }

        // Performing a first query to determine the total number of hits:
        $countQuery = $query->getQuery()
            ->cloneWithout(['groups', 'orders', 'offset', 'limit'])
            ->cloneWithoutBindings(['order']);
        $results = ['nbHits' => $countQuery->count($countQuery->getConnection()->raw('distinct record_id'))];

        // Preparing the actual query:
        $results['query'] = $query->with('record');

        return $results;
    }
}
