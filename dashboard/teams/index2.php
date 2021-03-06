<?php 
namespace Dashboard;

use Model\Model\SportsQuery;
use PDO;
require(__DIR__ . '/../../bootstrap.php');
//blocks users who are not logged in from visiting this page
$auth->onlyLoggedIn();
$auth->onlyVerified();

$sports = SportsQuery::create()->find();
$formsubmitted = $_SERVER['REQUEST_METHOD'] == "POST"; 
if($formsubmitted){
	$errors = [];
	$ids = $_POST['team_member_ids'];
	if(count($ids)!=4){ 
		// for testing the members must be 4
		// we'll have 11 once the application is complete
		$errors['team members'] = "There must be exactly 11 members in a team!";
	}
	
	foreach(array_count_values($ids) as $dupids){
		if ($dupids != 1){
			$errors['duplicates'] = "You've selected one team member more than once!";
		}
	}
	$teamname = trim(strip_tags($_POST['teamname']??''));
	if(strlen($teamname)<5 || strlen($teamname)>50)
		$errors['teamname'] = "Team name too long or too short!";
	if(isset($_POST['sport']))
		$sport = $_POST['sport'];
	else
		$errors['sport'] = "Please select a sport";
	if(!count($errors)){
		// validate that the sport is a valid sport
		$stmt = $mpdo->prepare("select * from sports where SportID = ?");
		$stmt->execute([$sport]);
		if(!$stmt->rowCount())
			$errors['sport'] = "Sport selected is not valid!";

		//validate the each member has already not participated in that sport already
// 		$in = join(',', array_fill(0, count($ids), '?'));
// 		if($stmt = $mpdo->prepare(
// <<<participatedquery
// select *
// from sportsteam
// where SportID = ?
// AND TeamID IN (
// 	SELECT TeamID
// 	from sportsparticipants
// 	where ParticipantID in ($in)
// )
// participatedquery
// )){
// 		$params = [$sport];
// 		$params = array_merge($params, $ids);
// 		$stmt->execute( $params);
// 		if($stmt->rowCount())
// 			$errors['team_members'] = "One or more of your team members are already enrolled in this sport!";
// 		}
// 		else{
// 			die($sth->errorInfo());
// 		}


		// populate the challan table
		$duedate = "12-10-17";
		$AmountPayable = 400;
		//find the new team ID first
		$stmt = $mpdo->prepare("select max(TeamID) as max from sportsteam");
		$stmt->execute();
		$teamID = $stmt->fetch();
		if(count($teamID))
			$teamID = $teamID[0];
		if($teamID == 0)
			$teamID = 1753;
		$challanID = "S".$teamID."e".$sport;
		$stmt = $mpdo->prepare("insert into challan(ChallanID, AmountPayable, DueDate, PaymentStatus) values(?,?,?,0)");
		$stmt->execute([$challanID, $AmountPayable, $duedate]);

		//populate the team table
		$stmt = $mpdo->prepare("insert into sportsteam(TeamID, SportID, TeamName, HeadCNIC, ChallanID, AmountPayable, DueData, PaymentStatus) values(?,?,?,?,?,?,?,0)");
		$HeadCNIC = $auth->getCNIC();
		$stmt->execute([$teamID, $sport, $teamname, $HeadCNIC, $challanID, $AmountPayable, $duedate]);

		//populate the sportsparticipant table
		$stmt = $mpdo->prepare("insert into sportsparticipants(TeamID, ParticipantID) values(?,?)");
		foreach($ids as $id){
			$stmt->execute([$teamID, $id]);
		}
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Make a team</title>
	<link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css">
</head>
<body>
<div class="container">
	
<?php if( $formsubmitted && count($errors) ): ?>
	<div class="alert-danger">
		<ul class="list-group">
		<?php foreach($errors as $field => $error): ?>
			<li class="list-group-item"><?=$error ?></li>
		<?php endforeach ?>
		</ul>
	</div>
<?php endif ?>
<h2>Make a new team</h2>

<form method="POST">

	<?php foreach($sports as $sport): ?>
	<div>
		<label>
			<input type="radio" value="<?=$sport->getSportID() ?>" name="sport">
			<?=$sport->getName() ?>
		</label>
	</div>
	<?php endforeach ?>
	<div class="form-group">
		<label class="control-label">
		Add a team member:
		</label>
		<input class="form-control" type="text" placeholder="Team Name" name="teamname">
	</div>
	<hr>
	<div>
		<label>
		Add a team member:
		<input type="text" placeholder="User ID" id="SearchTeamId">
		</label>
		<button id="SearchBtn">Search</button>
	</div>
	<h3>Members added:</h3>
	<div>
		<?=$auth->User()->getUsername() ?>
		<input type="hidden" value="<?=$auth->getParticipant()->getParticipantID()?>" name="team_member_ids[]">
	</div>
	<div id="members"></div>
	<hr>
	<button type="submit">Send</button>
</form>
</div>


<script type="text/javascript" src="..\..\js\jquery.min.js">
</script>
<script type="text/javascript">
$(function(){
	$("#SearchBtn").click(function(e){
		console.log($("#SearchTeamId").val());
		var destination="/dashboard/search.php";
		$.post(destination, {'id' : $("#SearchTeamId").val()}, function(result){
			if(result){
				$result = JSON.parse(result);
				console.log($result);
				$newelement = "<div class='member'><h3>";
				$newelement += $result.Firstname + " " + $result.Lastname;
				$newelement += "</h3>";
				$newelement += "<input type='hidden' name='team_member_ids[]' value='"+$result.Participantid+"' >";
				$newelement += "</div>";
				$("#members").append($newelement);
				console.log($newelement);
			}
  		});
		e.preventDefault();
	});
});
</script>
</body>	

</html>