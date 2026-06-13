<?php
  $delete = (isset($_POST['delete']))  ? $_POST['delete'] : null;
  $action = (isset($_POST['action']))  ? $_POST['action'] : null;
?>
<?php
  /* == Delete == */
  switch ($delete):


	 case "deletepages":
		/* == deletion codes == */
		
		
		/* == deletion codes == */
	 break;
	  
  endswitch;


  switch ($action):

	case "processPages":
	
		/* == add or update codes == */
		echo "
			<div class=\"alert bg-success alert-dismissible\" role=\"alert\"> 
				<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">
					<span aria-hidden=\"true\">×</span>
				</button>
				<strong>Success!</strong> Page successfully added.. 
			</div>
		";
		/* == add or update codes == */
		
	break;
	
  endswitch;
?>

