<?php

require_once "ScenarioGhost.php";

class RunnableTest
{

    private static $url = "https://redmine.smartdesign.by/";
    private static $login = "v.lapytsko";
    private static $password = "53775377";
    private static $arrOk = array();


//    private static $evil_tree = [
//        [
//            "tag" => "00-00",
//            "responsible" => "1",
//            "dependent" => [
//                [
//                    "tag" => "01-00",
//                    "responsible" => "1",
//                    "dependent" => []
//                ],
//                [
//                    "tag" => "01-01",
//                    "responsible" => "1",
//                    "dependent" => [
//                        [
//                            "tag" => "02-00",
//                            "responsible" => "1",
//                            "dependent" => [
//                                [
//                                    "tag" => "02-01",
//                                    "responsible" => "1",
//                                    "dependent" => []
//                                ]
//                            ]
//                        ]
//                    ]
//                ]
//            ]
//        ]
//    ];

    private static function buildScenariosDependent($x, $scenarioGhost = null)
    {
        $thisScenario = new ScenarioGhost($scenarioGhost, $x['tag'], $x['responsible']);
        while (true) {
            $countDep = count($x['dependent']);
            if ($countDep > 0) {
                for ($i = 0; $i < $countDep; $i++) {
                    $thisScenario->addScenarioDep(self::buildScenariosDependent($x['dependent'][$i], $thisScenario));
                }

            }
            return $thisScenario;
            break;
        }
    }

    /**
     * @param string $tag
     * @return bool
     */
    private static function callTestByTag($tag)
    {
        $execText = "/home/meldon/PhpstormProjects/All4bom_TA/RunnableTests/runnable.sh -t " . $tag;
        $resultExec = shell_exec($execText);
//        print $resultExec;
        if (stripos($resultExec, "Проваленные сценарии") === false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param ScenarioGhost $scenario
     * @return bool
     */
    private static function starScenarioTest($scenario)
    {
        $tag = $scenario->getTag();
        if (self::callTestByTag($tag)) {
            $countScenaries = count($scenario->getScenariosDep());
            for ($i = 0; $i < $countScenaries; $i++) {
                self::starScenarioTest($scenario->getScenariosDep()[$i]);
            }
            print "OK." . $scenario->getTag() . "\n";
            return true;
        } else {
            print "NO OK." . $scenario->getTag() . "\n";
            self::definitionOfResult($scenario);
            array_push(self::$arrOk,$scenario->getTag());
            return false;
        }
    }

    /**
     * @param ScenarioGhost $scenario
     */
    private static function definitionOfResult($scenario)
    {
        $thisScenarioTag = $scenario->getTag();
        $lastScenarioTag = null;
        if (!is_null($scenario->getLastScenario())) {
            $lastScenarioTag = $scenario->getLastScenario()->getTag();
        }

        $thisResultScenario = self::callTestByTag($thisScenarioTag);
        $resultLastScenario = null;
        if (!is_null($scenario->getLastScenario())) {
            $resultLastScenario = self::callTestByTag($lastScenarioTag);
        }

        if (
            ($thisResultScenario == true && $resultLastScenario == true) ||
            ($thisResultScenario == true && $resultLastScenario == null)
        ) {
            print "BREAK\n";
            return;
        } elseif (
            ($thisResultScenario == false && $resultLastScenario == true) ||
            ($thisResultScenario == false && $resultLastScenario == null)
        ) {
            print "REPORT" . $thisScenarioTag . "\n";
//            TODO
            $execTextReport = "./record*.sh -t " . $thisScenarioTag;
            echo shell_exec($execTextReport);

        } elseif (
            ($thisResultScenario == false && $resultLastScenario == false) ||
            ($thisResultScenario == true && $resultLastScenario == false)
        ) {
            self::definitionOfResult($scenario->getLastScenario());
            print "defOfRes\n";
        }
    }

    static function run()
    {
        $file = file_get_contents("tests.json");
        $evil_tree = json_decode($file, true);
        $scenario = self::buildScenariosDependent($evil_tree[0]);
        self::starScenarioTest($scenario);
        print PHP_EOL.PHP_EOL."=========FAILS=========";
        print_r(self::$arrOk);
        print PHP_EOL.PHP_EOL."=======================";
    }


    /**
     * @param string $tag
     * @param ScenarioGhost $scenario
     * @return ScenarioGhost
     */
    private static function getScenarioByTag($tag, $scenario)
    {
        if ($scenario->getTag() !== $tag) {
            $depCount = count($scenario->getScenariosDep());
            if ($depCount > 0) {
                for ($i = 0; $i < $depCount; $i++) {
                    $result = self::getScenarioByTag($tag, $scenario->getScenariosDep()[$i]);
                    if ($result != null) {
                        return $result;
                    }
                }
            }
        } else {
            return $scenario;
        }
    }

    static function runByTag($tag)
    {
        $file = file_get_contents("/home/meldon/PhpstormProjects/All4bom_TA/RunnableTests/tests.json");
        $evil_tree = json_decode($file, true);
        $scenario = self::buildScenariosDependent($evil_tree[0]);
        $scenario = self::getScenarioByTag($tag, $scenario);
//        var_dump($scenario->getTag());
//        self::starScenarioTest($scenario);
        return self::callTestByTag($tag);
    }

    static function runByTagAndTitle($tag, $title)
    {
        $execText = "./runnable.sh -t " . $tag . " -g" . $title;
        $resultExec = shell_exec($execText);

        if (stripos($resultExec, "Проваленные сценарии") === false) {
            return true;
        } else {
            return false;
        }
    }

    static function runSmoke()
    {
        $file = file_get_contents("tests.json");
        $evil_tree = json_decode($file, true);
        $scenario = self::buildSmokeScenariosDependent($evil_tree[0]);
//        var_dump($scenario);
        self::starScenarioTest($scenario);
        print PHP_EOL.PHP_EOL."=========FAILS=========";
        print_r(self::$arrOk);
        print PHP_EOL.PHP_EOL."=======================";
    }


    private static function buildSmokeScenariosDependent($x, $scenarioGhost = null)
    {
        if ($x['isSmoke'] == true) {
            $thisScenario = new ScenarioGhost($scenarioGhost, $x['tag'], $x['responsible']);
            while (true) {
                $countDep = count($x['dependent']);
                if ($countDep > 0) {
                    for ($i = 0; $i < $countDep; $i++) {
                        $result = self::buildSmokeScenariosDependent($x['dependent'][$i], $thisScenario);
                        if($result!==null)
                        $thisScenario->addScenarioDep($result);
                    }
                }
                return $thisScenario;
                break;
            }
        }else{
            return null;
        }
    }
}

