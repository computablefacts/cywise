<?php

namespace Tests\Unit\WithDb;

use App\Http\Controllers\CyberBuddyController;
use Tests\TestCase;

/** @deprecated */
class CyberBuddyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Disable tests with DB.');
    }
    protected function tearDown(): void
    {
        // $this->markTestSkipped('Disable tests with DB.');
    }

    public function testItRemovesSourcesFromAnswer()
    {
        $html = CyberBuddyController::removeSourcesFromAnswer("La source est [[36]].");
        $this->assertEquals("La source est .", $html);

        $html = CyberBuddyController::removeSourcesFromAnswer("La source est [[36],[44]].");
        $this->assertEquals("La source est .", $html);
    }

    public function testItCreatesATooltipWhenASingleReferenceIsUsed()
    {
        $tooltip = 'La source est <b style="color:#F8B500">[36]</b>.<br><br><b>Sources :</b><ul style="padding:0">
                  <li style="padding:0;margin-bottom:0.25rem">
                    <b style="color:#F8B500">[36]</b>&nbsp;
                    <div class="cb-tooltip-list">
                      
                      <span class="cb-tooltiptext cb-tooltip-list-top" style="background-color:#F8B500;color:#444;">
                        Source !
                      </span>
                    </div>
                  </li>
                </ul>';

        $html = CyberBuddyController::enhanceAnswerWithSources(
            "La source est [[36]].",
            collect([[
                'id' => '36', // string
                'text' => 'Source !',
            ]]));

        $this->assertEquals($tooltip, $html);

        $html = CyberBuddyController::enhanceAnswerWithSources(
            "La source est [[36]].",
            collect([[
                'id' => 36, // number
                'text' => 'Source !',
            ]]));

        $this->assertEquals($tooltip, $html);
    }

    public function testItCreatesATooltipWhenMultipleReferencesAreUsed()
    {
        $tooltip = 'La source est <b style="color:#F8B500">[36]</b>,<b style="color:#F8B500">[44]</b>.<br><br><b>Sources :</b><ul style="padding:0">
                  <li style="padding:0;margin-bottom:0.25rem">
                    <b style="color:#F8B500">[36]</b>&nbsp;
                    <div class="cb-tooltip-list">
                      
                      <span class="cb-tooltiptext cb-tooltip-list-top" style="background-color:#F8B500;color:#444;">
                        Source n°1
                      </span>
                    </div>
                  </li>
                
                  <li style="padding:0;margin-bottom:0.25rem">
                    <b style="color:#F8B500">[44]</b>&nbsp;
                    <div class="cb-tooltip-list">
                      
                      <span class="cb-tooltiptext cb-tooltip-list-top" style="background-color:#F8B500;color:#444;">
                        Source n°2
                      </span>
                    </div>
                  </li>
                </ul>';

        $html = CyberBuddyController::enhanceAnswerWithSources(
            "La source est [[36],[44]].",
            collect([[
                'id' => '36', // string
                'text' => 'Source n°1',
            ], [
                'id' => 44, // number
                'text' => 'Source n°2',
            ]]));

        $this->assertEquals($tooltip, $html);
    }
}
