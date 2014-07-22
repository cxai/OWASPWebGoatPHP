<?php

class CodingModeController extends JCatchControl
{
    public function Handle($request)
    {
        // This gives complete request path
        $request = jf::$BaseRequest;    //FIXME: Fix JCatchControl so that this is not required

        if (jf::CurrentUser()) {    // If user is logged in

            // Permissions required for this mode
            // are same as the single user mode
            if (jf::Check('view_single_chal')) {
                // Extract the relative request path
                // i.e the path after the controller URL
                // Ex: If request is http://localhost/webgoatphp/mode/single/challenges/HTTPBasics/static/test
                // $request will be mode/single/challenges/HTTPBasics/static/test
                // $relativePath will be HTTPBasics/static/test
                $relativePath = $this->getRelativePath($request);
                $absolutePath = LESSON_PATH.$relativePath;

                if (strpos($relativePath, "/static/") !== false) {
                    if (file_exists($absolutePath)) {
                        $FileMan = new \jf\DownloadManager();
                        return $FileMan->Feed($absolutePath);
                    }
                } else {
                    $nameOfLesson = stristr($relativePath, "/", true);
                    \webgoat\LessonScanner::loadClasses();

                    if (strpos($relativePath, "reset/") !== false) {
                        $lessonNameWithNS = "\\webgoat\\".$nameOfLesson;
                        $obj = new $lessonNameWithNS();
                        $obj->reset();

                        echo json_encode(array("status" => true));
                        return true;
                    } else {
                        if (((isset($_GET['refresh'])) || (!jf::LoadGeneralSetting("categoryLessons")))) {
                            \webgoat\LessonScanner::run();
                        }

                        $this->allCategoryLesson = jf::LoadGeneralSetting("categoryLessons");
                        try {
                            $lessonObj = \webgoat\LessonScanner::getLessonObject($nameOfLesson);
                            $lessonObj->start();
                            $this->lessonTitle = $lessonObj->getTitle();
                            $this->hints = $lessonObj->getHints();
                            $this->htmlContent = $lessonObj->getContent();
                            $this->nameOfLesson = $nameOfLesson;

                            $secureCoding = $lessonObj->isSecureCodingAllowed();
                            $sourceCodeToDisplay = "";
                            if ($secureCoding['status'] === true) {
                                $sourceCode = file($absolutePath."index.php");
                                for ($i = $secureCoding['start']; $i < $secureCoding['end']; $i++) {
                                    $sourceCodeToDisplay .= (trim($sourceCode[$i])."\n");
                                }
                                $this->sourceCode = $sourceCodeToDisplay;
                            }

                            if (isset($_POST['sourceCode'])) {
                                // Code to handle source code evaluation
                            }

                        } catch (Exception $e) {
                            //$this->error = "Lesson Not found. Please select a lesson.";
                            $this->error = $e->getMessage();
                        }

                        return $this->Present();
                    }
                }

            } else {
                // Not sufficient permissions, redirect
                // to home page of the application
                $this->Redirect(SiteRoot);
            }

        } else {
            // User not logged in
            $this->Redirect(jf::url()."/user/login?return=/$request");
        }
    }

    private function getRelativePath($request)
    {
        $presentDir = basename(dirname(__FILE__));
        return substr($request, (strpos($request, $presentDir) + strlen($presentDir) + 1));
    }
}
