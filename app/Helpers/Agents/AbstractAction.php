<?php

namespace App\Helpers\Agents;

use App\Models\User;

/** @deprecated */
abstract class AbstractAction
{
    protected User $user;
    protected string $threadId;
    protected array $messages;
    protected array $args;
    protected mixed $output;

    abstract static function schema(): array;

    public static function htmlTable(string $header, string $rows, int $nbCols): string
    {
        $rows = empty($rows) ? "<tr><td colspan='{$nbCols}' style='text-align: center'>No data available.</td></tr>" : $rows;
        return "
            <div class='tw-answer-table-wrapper'>
              <div class='tw-answer-table'>
                <table>
                  <thead>
                    <tr>
                      {$header}
                    </tr>
                  </thead>
                  <tbody>
                    {$rows}
                  </tbody>
                </table>
              </div>
            </div>
        ";
    }

    public function __construct(User $user, string $threadId, array $messages, array $args = [])
    {
        $this->user = $user;
        $this->threadId = $threadId;
        $this->messages = $messages;
        $this->args = $args;
    }

    public function name(): string
    {
        return $this->schema()['function']['name'] ?? '';
    }

    public function memoize(): bool
    {
        return true;
    }

    public function html(): string
    {
        return $this->output;
    }

    public function text(): string
    {
        return $this->output;
    }

    public function markdown(): string
    {
        return $this->output;
    }

    abstract function execute(): AbstractAction;
}
