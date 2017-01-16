<?php

require_once "TextBugReport/TextReport.php";
 require_once "RedmineReport/RedmineSimpleReport.php";

class Report
{

    private $isPrivate;
    private $status;
    private $priority;
    private $attachment;
    private $assignedUserId;
    private $passStepsLines;
    private $trigerFailStep;

    private $textReport;


    private $urlRedmine;
    private $userRedmine;
    private $passwordRedmine;
    private $nameProject;
    private $isRerun;

    /**
     * TextReport constructor.
     * @param $projectId
     * @param $title
     * @param $description
     * @param $isPrivate
     * @param $status
     * @param $priority
     * @param $attachment
     * @param $assignedUserId
     */
    public function __construct($isPrivate, $status, $priority, $assignedUserId, $urlRedmine, $userRedmine, $passwordRedmine, $nameProject)
    {
        $this->isPrivate = $isPrivate;
        $this->status = $status;
        $this->priority = $priority;
        $this->assignedUserId = $assignedUserId;

        $this->passStepsLines = array();
        $this->trigerFailStep = false;
        $this->textReport = new TextReport();

        $this->urlRedmine = $urlRedmine;
        $this->userRedmine = $userRedmine;
        $this->passwordRedmine = $passwordRedmine;
        $this->nameProject = $nameProject;
        $this->isRerun = $this->isRerun();
    }


    private function isRerun()
    {
        if (file_exists("scenario.rerun")) {
            return true;
        } else {
            return false;
        }
    }

    private function setAllStepsInTextReport($afterScenarioScope)
    {
        $fullSteps = "";
        foreach ($afterScenarioScope->getScenario()->getSteps() as $step) {
            $fullSteps = $fullSteps . "# " . $step->getText() . "\n";

        }
        $this->textReport->setFullSteps($fullSteps);
    }

    private function getPriorityID($afterScenarioScope){

    }

    public function afterScenario($afterScenarioScope)
    {
        $this->setAllStepsInTextReport($afterScenarioScope);


        if ($this->isRerun) {
            $this->textReport->afterScenario();
            $report = new RedmineSimpleReport($this->urlRedmine, $this->userRedmine, $this->passwordRedmine, $this->nameProject);

            if($this->textReport->isGiven()){
//                Set priority with ID=4
                $this->priority = 4;
            }

            $report->createIssue($this->textReport->getTitle(), $this->textReport->getDescription(), $this->priority, $this->assignedUserId);
        }
    }

    public function afterStep($afterStepScope,$webDriver)
    {
        if ($this->isRerun) {
            $this->textReport->afterStep($afterStepScope,$webDriver);
        }
    }

    public function beforeScenario($beforeScenarioScope)
    {
        if ($this->isRerun) {
            $this->textReport->beforeScenario($beforeScenarioScope);
        }
    }


}