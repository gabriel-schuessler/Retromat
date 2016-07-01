<?php

namespace tests\AppBundle\Importer\Activity;

use AppBundle\Importer\Activity\OriginalRetromatActivityImporter;

class OriginalRetromatActivityImporterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OriginalRetromatActivityImporter
     */
    private $importer;

    public function setUp()
    {
        $activityFileName = __DIR__.'/../../../../../lang/activities_en.php';
        $this->importer = new OriginalRetromatActivityImporter($activityFileName);
    }

    public function testExtractActivityBlock()
    {
        $expected = <<<'HTML'
phase:     0,
name:      "ESVP",
summary:   "How do participants feel at the retro: Explorer, Shopper, Vacationer, or Prisoner?",
desc:      "Prepare a flipchart with areas for E, S, V, and P. Explain the concept: <br>\
<ul>\
    <li>Explorer: Eager to dive in and research what did and didn't work and how to improve.</li>\
    <li>Shopper: Positive attitude. Happy if one good things comes out.</li>\
    <li>Vacationer: Reluctant to actively take part but the retro beats the regular work.</li>\
    <li>Prisoner: Only attend because they (feel they) must.</li>\
</ul>\
Take a poll (anonymously on slips of paper). Count out the answers and keep track on the flipchart \
for all to see. If trust is low, deliberately destroy the votes afterwards to ensure privacy. Ask \
what people make of the data. If there's a majority of Vacationers or Prisoners consider using the \
retro to discuss this finding.",
source:  source_agileRetrospectives,
duration:  "5-10 numberPeople",
suitable:   "iteration, release, project, immature"
HTML;
        $this->assertEquals($expected, $this->importer->extractActivityBlock(0));

        $expected = <<<'HTML'
phase:     0,
name:      "Weather Report",
summary:   "Participants mark their 'weather' (mood) on a flipchart",
desc:      "Prepare a flipchart with a drawing of storm, rain, clouds and sunshine.\
Each participant marks their mood on the sheet.",
source:  source_agileRetrospectives,
HTML;
        $this->assertEquals($expected, $this->importer->extractActivityBlock(1));
    }

    public function testExtractActivityPhaseMissing()
    {
        $activityBlock = <<<'HTML'
name:      "Check In - Quick Question", // TODO This can be expanded to at least 10 different variants - how?
summary:   "Ask one question that each participant answers in turn",
HTML;
        $this->expectException('AppBundle\Importer\Activity\Exception\ActivitySyntaxException');
        $this->importer->extractActivityPhase($activityBlock);
    }


    public function testExtractActivityPhaseNotFirstInBlock()
    {
        $activityBlock = <<<'HTML'
name:      "Check In - Quick Question", // TODO This can be expanded to at least 10 different variants - how?
phase:     0,
summary:   "Ask one question that each participant answers in turn",
HTML;
        $this->expectException('AppBundle\Importer\Activity\Exception\ActivitySyntaxException');
        $this->importer->extractActivityPhase($activityBlock);
    }

    public function testExtractActivityPhase()
    {
        $activityBlock = <<<'HTML'
phase:     3,
name:      "foo",
summary:   "bar",
HTML;
        $this->assertEquals(3, $this->importer->extractActivityPhase($activityBlock));

        $activityBlock = <<<'HTML'
phase:     0,
name:      "Check In - Quick Question", // TODO This can be expanded to at least 10 different variants - how?
summary:   "Ask one question that each participant answers in turn",
HTML;
        $this->assertEquals(0, $this->importer->extractActivityPhase($activityBlock));
    }

    public function testExtractActivityPhaseWhenPhaseHasJSComment()
    {
        $activityBlock = <<<'HTML'
phase:     4, // 5 geht auch
name:      "SaMoLo (More of, Same of, Less of)",
HTML;
        $this->assertEquals(4, $this->importer->extractActivityPhase($activityBlock));
    }

    public function testExtractActivityName()
    {
        $activityBlock = <<<'HTML'
phase:     0,
name:      "Check In - Amazon Review",
HTML;
        $this->assertEquals('Check In - Amazon Review', $this->importer->extractActivityName($activityBlock));
    }

    public function testExtractActivityNameWhenPhaseHasJSComment()
    {
        $activityBlock = <<<'HTML'
phase:     4, // 5 geht auch
name:      "SaMoLo (More of, Same of, Less of)",
HTML;
        $this->assertEquals('SaMoLo (More of, Same of, Less of)', $this->importer->extractActivityName($activityBlock));
    }

    public function testExtractSummary()
    {
        $activityBlock = <<<'HTML'
phase:     0,
name:      "Check In - Quick Question", // TODO This can be expanded to at least 10 different variants - how?
summary:   "Ask one question that each participant answers in turn",
desc:      "In round-robin each participant answers the same question (unless they say 'I pass'). \
Sample questions: <br>\
<ul>\
    <li>In one word - What do you need from this retrospective?</li>\
Address concerns, e.g. by writing it down and setting it - physically and mentally - aside</li>\
    <li>In this retrospective - If you were a car, what kind would it be?</li>\
    <li>What emotional state are you in (e.g. 'glad', 'mad', 'sad', 'scared'?)</li>\
</ul><br>\
Avoid evaluating comments such as 'Great'. 'Thanks' is okay.",
source:  source_agileRetrospectives
HTML;
        $this->assertEquals(
            'Ask one question that each participant answers in turn',
            $this->importer->extractActivitySummary($activityBlock)
        );
    }

    public function testExtractSummaryKeyCanAppearInValue()
    {
        $activityBlock = <<<'HTML'
phase:     0,
name:      "Magic sprint summary: Quick Question",
summary:   "Ask one question that each participant answers in turn",
HTML;
        $this->assertEquals(
            'Ask one question that each participant answers in turn',
            $this->importer->extractActivitySummary($activityBlock)
        );
    }

    public function testExtractDescription()
    {
        $activityBlock = <<<'HTML'
phase:     0,
name:      "Check In - Quick Question", // TODO This can be expanded to at least 10 different variants - how?
summary:   "Ask one question that each participant answers in turn",
desc:      "In round-robin each participant answers the same question (unless they say 'I pass'). \
Sample questions: <br>\
<ul>\
    <li>In one word - What do you need from this retrospective?</li>\
Address concerns, e.g. by writing it down and setting it - physically and mentally - aside</li>\
    <li>In this retrospective - If you were a car, what kind would it be?</li>\
    <li>What emotional state are you in (e.g. 'glad', 'mad', 'sad', 'scared'?)</li>\
</ul><br>\
Avoid evaluating comments such as 'Great'. 'Thanks' is okay.",
source:  source_agileRetrospectives
HTML;

        $expected = <<<'HTML'
In round-robin each participant answers the same question (unless they say 'I pass'). \
Sample questions: <br>\
<ul>\
    <li>In one word - What do you need from this retrospective?</li>\
Address concerns, e.g. by writing it down and setting it - physically and mentally - aside</li>\
    <li>In this retrospective - If you were a car, what kind would it be?</li>\
    <li>What emotional state are you in (e.g. 'glad', 'mad', 'sad', 'scared'?)</li>\
</ul><br>\
Avoid evaluating comments such as 'Great'. 'Thanks' is okay.
HTML;

        $this->assertEquals($expected, $this->importer->extractActivityDescription($activityBlock));
    }

    public function testExtractSourcePlaceholderLastLine()
    {
        $activityBlock = <<<'HTML'
phase:     0,
name:      "Temperature Reading",
summary:   "Participants mark their 'temperature' (mood) on a flipchart",
desc:      "Prepare a flipchart with a drawing of a thermometer from freezing to body temperature to hot. \
Each participant marks their mood on the sheet.",
source:  source_unknown,
HTML;

        $this->assertEquals('source_unknown', $this->importer->extractActivitySource($activityBlock));
    }
    public function testExtractSourcePlaceholderLastLineNoComma()
    {
        $activityBlock = <<<'HTML'
phase:     0,
name:      "Check In - Quick Question", // TODO This can be expanded to at least 10 different variants - how?
summary:   "Ask one question that each participant answers in turn",
desc:      "In round-robin each participant answers the same question (unless they say 'I pass'). \
Sample questions: <br>\
<ul>\
    <li>In one word - What do you need from this retrospective?</li>\
Address concerns, e.g. by writing it down and setting it - physically and mentally - aside</li>\
    <li>In this retrospective - If you were a car, what kind would it be?</li>\
    <li>What emotional state are you in (e.g. 'glad', 'mad', 'sad', 'scared'?)</li>\
</ul><br>\
Avoid evaluating comments such as 'Great'. 'Thanks' is okay.",
source:  source_agileRetrospectives
HTML;

        $this->assertEquals('source_agileRetrospectives', $this->importer->extractActivitySource($activityBlock));
    }

    public function testExtractSourceStringAndPlaceholder()
    {
        $activityBlock = <<<'HTML'
phase:     4,
name:      "Feedback Door - Numbers",
summary:   "Gauge participants' satisfaction with the retro on a scale from 1 to 5 in minimum time",
desc:      "Put sticky notes on the door with the numbers 1 through 5 on them. 1 is the topmost and best, \
5 the lowest and worst.\
When ending the retrospective, ask your participants to put a sticky to the number they feel \
reflects the session. The sticky can be empty or have a comment or suggestion on it.",
source:    "ALE 2011, " + source_findingMarbles,
duration:  "2-3",
suitable: "iteration, largeGroups"
HTML;

        $expected = <<<'HTML'
"ALE 2011, " + source_findingMarbles
HTML;

        $this->assertEquals($expected, $this->importer->extractActivitySource($activityBlock));
    }

    public function testExtractSourcePlaceholderAndString()
    {
        $activityBlock = <<<'HTML'
phase:     4,
name:      "Appreciations",
summary:   "Let team members appreciate each other and end positively",
desc:      "Start by giving a sincere appreciation of one of the participants. \
It can be anything they contributed: help to the team or you, a solved problem, ...\
Then invite others and wait for someone to work up the nerve. Close, when no one \
has talked for a minute.",
source:    source_agileRetrospectives + " who took it from 'The Satir Model: Family Therapy and Beyond'",
duration:  "5-30 groupsize",
suitable: "iteration, release, project"
HTML;

        $expected = <<<'HTML'
source_agileRetrospectives + " who took it from 'The Satir Model: Family Therapy and Beyond'"
HTML;

        $this->assertEquals($expected, $this->importer->extractActivitySource($activityBlock));
    }

    public function testExtractSourceString()
    {
        $activityBlock = <<<'HTML'
phase:     4, // 5 geht auch
name:      "SaMoLo (More of, Same of, Less of)",
summary:   "Get course corrections on what you do as a facilitator",
desc:      "Divide a flip chart in 3 sections titled 'More of', 'Same of', and 'Less of'. \
Ask participants to nudge your behaviour into the right direction: Write stickies \
with what you should do more, less and what is exactly right. Read out and briefly \
discuss the stickies section-wise.",
source:    "<a href='http://fairlygoodpractices.com/samolo.htm'>Fairly good practices</a>",
more:      "<a href='http://www.scrumology.net/2010/05/11/samolo-retrospectives/'>David Bland's experiences</a>",
duration:  "5-10",
suitable: "iteration, release, project"
HTML;

        $expected = <<<'HTML'
"<a href='http://fairlygoodpractices.com/samolo.htm'>Fairly good practices</a>"
HTML;

        $this->assertEquals($expected, $this->importer->extractActivitySource($activityBlock));
    }


    public function testExtractSourceKeyCanAppearInValue()
    {
        $activityBlock = <<<'HTML'
phase:     4, // 5 geht auch
name:      "SaMoLo (More of, Same of, Less of)",
summary:   "Get course corrections on what you do as a facilitator",
desc:      "Use the source: This is important for Jedi knights.",
source:    "<a href='http://fairlygoodpractices.com/samolo.htm'>Fairly good practices</a>",
more:      "<a href='http://www.scrumology.net/2010/05/11/samolo-retrospectives/'>David Bland's experiences</a>",
duration:  "5-10",
suitable: "iteration, release, project"
HTML;

        $expected = <<<'HTML'
"<a href='http://fairlygoodpractices.com/samolo.htm'>Fairly good practices</a>"
HTML;

        $this->assertEquals($expected, $this->importer->extractActivitySource($activityBlock));
    }
}