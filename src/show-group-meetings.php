<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use \ChurchTools\Api\Tools\CalendarTools;

if (file_exists ( 'config.php' ) )
{
    $configs = include('config.php');
}
else
{
    $configs= null;
}
//$serverURL= $configs["serverURL"];

$serverURL= filter_input(INPUT_POST, "serverURL");
$userName= filter_input(INPUT_POST, "email");
$password= filter_input(INPUT_POST, "password");

session_start();
if ($serverURL == null)
{
    $userName= $_SESSION["userName"];
    $password= $_SESSION["password"];
    $serverURL= $_SESSION["serverURL"];
}
else
{
    $_SESSION["userName"]= $userName;
    $_SESSION["password"]= $password;
    $_SESSION["serverURL"]= $serverURL;
}

$groupType= filter_input(INPUT_POST, "GROUPTYPE");
$showLeader= filter_input(INPUT_POST, "SHOW_LEADER");

$hasError= false;
$errorMessage= null;
try
{
    $api = \ChurchTools\Api\RestApi::createWithUsernamePassword($serverURL,
            $userName, $password);
    $personMasterData= $api->getPersonMasterData();

    $visibleGroupTypes= $personMasterData->getGroupTypes();
    $visibleGroups= $personMasterData->getGroups();
    $selectedGroups= $visibleGroups->getGroupsOfType($groupType);
    $_SESSION["selectedGroups"]= $selectedGroups;
}
catch (Exception $e)
{
    $errorMessage= $e->getMessage();
    $hasError= true;
    session_destroy();
}
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Churchtools Gruppentreffen</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous" />        
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <div class="container">
            <h1>Churchtools Gruppentreffen</h1>
            <?php if ($hasError) { ?>
            <h2>Login fehlgeschlagen</h2>
            <div class="alert alert-danger" role="alert">
            Error in login: <?= $errorMessage ?>
            </div>
            <div>
                <a href="index.php" class="btn btn-primary">Zum Login</a>
            </div>
            <?php } else { ?>
            <?php 
                    foreach( $selectedGroups as $group) { ?>
            <?php
                    $meetings= $api->getGroupMeetings($group->getId());
                    if ($meetings != null && sizeof($meetings) > 0) {
            ?>
                <h2 class="group-title"><?= $group->getTitle() ?></h2>
                    <?php foreach ($meetings as $meeting) { 
                        $pollResult= $meeting->getPollResult();
                        $remainingResults= [];
                        $untilTime= null;
                        $ort= null;
                        if ($pollResult != null) {
                            foreach ($pollResult as $result) {
                                $v= $result["value"];
                                if ($v != null && $v != "") {
                                    if ($result["label"] == "Bis" || $result["label"] == "Dauer")
                                    {
                                        $untilTime= $v;
                                    }
                                    elseif ($result["label"] == "Ort?" || $result["label"] == "Ort")
                                    {
                                        $ort= $v;
                                    }
                                    elseif ($result["label"] == "Anderer Ort")
                                    {
                                        $ort= $v;
                                    }
                                    else
                                    {
                                        $remainingResults[$result["label"]]= $v;
                                    }
                                }
                            }
                        }
                        ?>
                    <div class="row">
                    <?php if ($meeting->isMeetingCanceled()) { ?><strike><?php } ?>
                    <div class="col-4 <?= $meeting->isMeetingCanceled() ? "bg-danger" : "" ?>"><?= $meeting->getStartDate()->format("d.m.Y H:i") ?> <?= ($untilTime != null ? "- ".$untilTime." " : "")?>
                        <?php if ($meeting->isMeetingCanceled()) { ?></strike><br />Treffen wurde abgesagt</span><?php } ?>
                    </div>
                        <div class="col-8">
                            <?php if ($ort != null) { ?>Ort: <?= $ort ?><?php } ?>
                    <?php 
                        if ($pollResult != null) {
                            foreach ($remainingResults as $key => $result) {
                                if ($result != null && $result != "") {
                                    echo "<br>".$key.": ".$result;
                                }
                            }
                        }
                    ?>
                    <?php if ($meeting->isMeetingCanceled()) { ?></strike><?php } ?>
                    </div>
                    </div>
                    <?php } ?>
                    <?php } } ?>
            </form>
             <div class="form-group row mt-2 ml-1">
<!--                 <input type="submit" value="Show meetings" class="btn btn-primary mr-1">-->
                 <a href="index.php" class="btn btn-secondary mr-1">Abmelden</a>
                 <a href="export-xlsx.php" class="btn btn-secondary mr-1" target="_blank">Export als XLSX Datei</a>
             </div>
            <?php } ?>
        </div>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    </body>
</html>
