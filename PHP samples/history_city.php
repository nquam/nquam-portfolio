<?

include (ROOT."lib/jpgraph-2.2/src/jpgraph.php");   
include (ROOT."lib/jpgraph-2.2/src/jpgraph_line.php");

$sql='SELECT * FROM `PM_history`.`total_by_city` WHERE `state` = "'.$state.'" && `city` = "'.$city.'"';
$result=query($mysqli,$sql);

$data['title'] = 'Total Counts for '.$city.', '.$state;
$data['cols'] = array('date','count','count_S','count_M','count_D','count_R','count_B');
$data['headers'] = array('Date','Total Properties','Single Family','Condo/Townhome','Multi-Family','Rental','Mobile Home');

while($row = mysqli_fetch_array($result)){
	$leg[] = date("m/d/Y",$row['date']);
	$data['date'][] = $row['date'];
	$data['count'][] = $row['count'];
	$data['count_S'][] = $row['count_S'];
	$data['count_M'][] = $row['count_M'];
	$data['count_D'][] = $row['count_D'];
	$data['count_R'][] = $row['count_R'];
	$data['count_B'][] = $row['count_B'];
}

$graph = new Graph(850,350,"auto");
$graph->SetScale("textint");
$graph->img->SetMargin(80,190,20,60);
$graph->SetMarginColor('white');
$graph->SetFrame(false);
//$graph->SetShadow();
$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->xaxis->SetLabelAngle(45);

	$graph->SetY2Scale( "lin");

	// Create the first line
	$p1=new LinePlot($data['count']);
	$p1->mark->SetType(MARK_SQUARE);
//	$p1->value->Show();
	$p1->mark->SetColor('#999999');
	$p1->mark->SetFillColor('#2DB306');
	$p1->mark->SetWidth(4);
	$p1->SetWeight(1);
	$p1->SetColor('#2DB306');
	$p1->SetCenter();
	$graph->AddY2($p1);

	$p2=new LinePlot($data['count_S']);
	$p2->SetWeight(3);
	$p2->SetColor('#D23131');
	$p2->SetCenter();
	$graph->Add($p2);
	
	$p3=new LinePlot($data['count_M']);
	$p3->SetWeight(3);
	$p3->SetColor('#C563A7');
	$p3->SetCenter();
	$graph->Add($p3);
	
	$p4=new LinePlot($data['count_D']);
	$p4->SetWeight(3);
	$p4->SetColor('#544EC2');
	$p4->SetCenter();
	$graph->Add($p4);
	
	$p5=new LinePlot($data['count_R']);
	$p5->SetWeight(3);
	$p5->SetColor('#4DC4C4');
	$p5->SetCenter();
	$graph->Add($p5);
	
	$p6=new LinePlot($data['count_B']);
	$p6->SetWeight(3);
	$p6->SetColor('#C9AF47');
	$p6->SetCenter();
	$graph->Add($p6);
	
	$p1->SetLegend("Total Count");
	$p2->SetLegend("Residential");
	$p3->SetLegend("Condo/Townhome");
	$p4->SetLegend("Multi-Family");
	$p5->SetLegend("Rental");
	$p6->SetLegend("Mobile Home");
	
	//$graph->legend->SetLayout(LEGEND_HOR);
	$graph->legend->Pos(0.91,0.55,"center","bottom");
	
	$graph->xaxis->SetTickLabels($leg);
	$graph->xaxis-> title->Set("date");
	$graph->yaxis-> title->Set("Property type");
	$graph->yaxis->SetTitlemargin(60);
	$graph->yaxis->SetColor("#000000");
	$graph->y2axis->SetColor("#208004");
	//$graph->Stroke();
	$path = ROOT.'media/stats/history/';
	$graph->Stroke($path.'history_'.$state.'_'.$city.'.gif');
?>