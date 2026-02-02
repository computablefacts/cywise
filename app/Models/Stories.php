<?php

namespace App\Models;

use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\WebpagesProvider;
use App\Enums\LanguageEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string news
 * @property LanguageEnum news_language
 * @property ?string hyperlink
 * @property ?string website
 * @property ?string teaser
 * @property ?string opener
 * @property ?string why_it_matters
 * @property ?string go_deeper
 * @property ?string teaser_fr
 * @property ?string opener_fr
 * @property ?string why_it_matters_fr
 * @property ?string go_deeper_fr
 * @property bool is_published
 */
class Stories extends Model
{
    use HasFactory;

    protected $table = 'tcb_stories';

    protected $fillable = [
        'news',
        'news_language',
        'hyperlink',
        'website',
        'teaser',
        'opener',
        'why_it_matters',
        'go_deeper',
        'teaser_fr',
        'opener_fr',
        'why_it_matters_fr',
        'go_deeper_fr',
        'is_published',
    ];

    protected $casts = [
        'language' => LanguageEnum::class,
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function brief(LanguageEnum $language): array
    {
        $summarizeEn = $language === LanguageEnum::ENGLISH && (!$this->teaser || !$this->opener || !$this->why_it_matters);
        $summarizeFr = $language === LanguageEnum::FRENCH && (!$this->teaser_fr || !$this->opener_fr || !$this->why_it_matters_fr);
        if ($summarizeEn || $summarizeFr) {

            $news = WebpagesProvider::isHyperlink($this->news) ? WebpagesProvider::provide($this->news) : $this->news;
            $brief = LlmsProvider::provide($this->prompt($language, $news));

            if (empty($brief)) {
                return [];
            }
            if (WebpagesProvider::isHyperlink($this->news)) {
                $this->hyperlink = Str::limit(trim($this->news), 500);
                $this->website = Str::before(Str::after($this->news, '://'), '/');
            }
            if ($language === LanguageEnum::FRENCH) {

                $this->teaser_fr = Str::trim(Str::between($brief, '[TEASER]', '[OPENER]'));
                $this->opener_fr = Str::trim(Str::between($brief, '[OPENER]', '[WHY_IT_MATTERS]'));

                if (!Str::contains($brief, '[GO_DEEPER]')) {
                    $this->why_it_matters_fr = Str::trim(Str::after($brief, '[WHY_IT_MATTERS]'));
                } else {
                    $this->why_it_matters_fr = Str::trim(Str::between($brief, '[WHY_IT_MATTERS]', '[GO_DEEPER]'));
                    $this->go_deeper_fr = Str::trim(Str::after($brief, '[GO_DEEPER]'));
                }
            } else {

                $this->teaser = Str::trim(Str::between($brief, '[TEASER]', '[OPENER]'));
                $this->opener = Str::trim(Str::between($brief, '[OPENER]', '[WHY_IT_MATTERS]'));

                if (!Str::contains($brief, '[GO_DEEPER]')) {
                    $this->why_it_matters = Str::trim(Str::after($brief, '[WHY_IT_MATTERS]'));
                } else {
                    $this->why_it_matters = Str::trim(Str::between($brief, '[WHY_IT_MATTERS]', '[GO_DEEPER]'));
                    $this->go_deeper = Str::trim(Str::after($brief, '[GO_DEEPER]'));
                }
            }
            $this->save();
        }
        return [
            'website' => $this->website,
            'link' => $this->hyperlink,
            'teaser' => $language === LanguageEnum::FRENCH ? $this->teaser_fr : $this->teaser,
            'opener' => $language === LanguageEnum::FRENCH ? $this->opener_fr : $this->opener,
            'why_it_matters' => $language === LanguageEnum::FRENCH ? $this->why_it_matters_fr : $this->why_it_matters,
            'go_deeper' => $language === LanguageEnum::FRENCH ? $this->go_deeper_fr : $this->go_deeper,
        ];
    }

    private function prompt(LanguageEnum $language, string $news): string
    {
        $lang = $language === LanguageEnum::FRENCH ? 'french' : 'english';
        return "
Below is the description of SmartBrevity's four parts news format:
[TEASER] Six or fewer strong words to catch someone's attention.
[OPENER] A single sentence that should tell me something I don't know, would want to know or should know. Make this sentence as direct, short and sharp as possible.
[WHY_IT_MATTERS] A few sentences or bullet points to explain why this new fact, idea or though matters.
[GO_DEEPER] A few sentences or paragraphs that expand on 'Why it matters' with greater details.

Be aware that:
- These four parts must fit in one screen of phone, regardless of what it is.
- The [TEASER], [OPENER] and [WHY_IT_MATTERS] parts are mandatory.
- The [GO_DEEPER] part is optional.
- Do not use Markdown.

Below is an example of an original news rewritten using SmartBrevity's news format:
- Original news:
  Title: Hey, there are some new plans for the weekend to discuss re: birthday party.
  Sorry for the late change of plans but there's been so much chaos in pulling Jimmi's party together especially with the weather this past week. The good news is we found a place to take all of the kids, that new trampoline park. We will do this Saturday at noon.
  The only hitch is it's a little farther than we originally planned. The first spot we were looking at was 30-minute drive, but trampoline park has a lot more space, so we picked it even though it's about 40 minutes away. Just flagging for planning purpose.
  The place is located at 1100 Wilson Street by that sushi restaurant we visited that had those awesome spider rolls. Ha ha. It starts at noon, and our session ends at 4 pm. Feel free to stay or go since we have the instructor and we will serve lunch and drinks. I will stay and read or worry. They should dress to play! Shorts and shirts, oh and socks requires... see you soon and sorry again.
- Rewritten news:
  [TEASER] New plan: trampoline park.
  [OPENER] We're moving Jimmy's party to the new trampoline park this Saturday at noon.
  [WHY_IT_MATTERS] It's about 40-minute drive, so you might need to leave a little earlier than we first thought.
  [GO_DEEPER] 
    • Arrive @ noon, 1100 Wilson Street. 
    • Pizza & drinks provided. 
    • Pick up kids @ 4pm. 
    • Dress to play. Socks REQUIRED.
    
Below is another example of an original news rewritten using SmartBrevity's news format:
- Original news:
  Title: Board of Directors Update
  We presented on our progress toward our go-to-market plan in our most recent Board of Directors meeting, Wednesday, including strong product sales over the last quarter within the scope of our beta test. We were able to “wow” the Board with a report including a 12 percent jump in revenue over the last quarter, which puts us an extraordinary 90 percent of the way to our overall goal for the second half.
  Strong product sales will allow us to increase investment in key early growth opportunities across tech and marketing. We’re updating the second-half roadmap with big investments in the tech team, particularly on the machine learning squad, marketing, to support Ava’s team with our new pitch and positioning, and in some exciting new collaborations with firms doing work where we don’t have internal capacity but do have a strategic need to add expertise.
  If you haven’t taken time to review Ava’s new pitch and positioning documents, we encourage everyone to do so. The new talking points went through a lot of testing with focus groups and reflect our best argument to date on why our solution is the best in the industry.
- Rewritten news:
  [TEASER] We wowed our Board
  [OPENER] We stunned the Board Wednesday with Q3’s 12% revenue jump, putting us 90% to goal for H2.
  [WHY_IT_MATTERS] Higher revenue means we can invest in two areas that will speed up our go-to-market plan by months.
    • New hires: We can add key machine learning roles on the tech and marketing teams.
    • Partnerships: We’ll finalize two deals to expand our skills and strategic thinking.
  [GO_DEEPER] Our product speaks for itself, but it was Ava’s new pitch—tested over three weeks of focus groups—that got it into customers’ hands.
    • Please review Ava’s materials on the intranet.

Now, take the following text and summarizes it in {$lang} using SmartBrevity's news format: {$news}
        ";
    }
}
