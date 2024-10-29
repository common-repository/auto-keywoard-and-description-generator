<?php
/*
Plugin Name: Auto Keywords and Description Generator
Plugin URI: http://michael.chanceyjr.com/free-stuff/auto-keywords-and-description-generator/
Description: Auto create Keywords and description meta tags from blog post information
Version: 1.3.2
Author: Michael E. Chancey Jr.
Author URI: http://michael.chanceyjr.com
*/

function generateMetaTags()
{
	global $post;
	
	$author = get_userdata($post->post_author)->display_name;
	$keywords = "";
	$description = "";
	
	//DETERMINE WHERE TO LOAD THE INFORMATION FROM
	if(is_home() || is_front_page())
	{
		//LOAD KEYWORDS FROM USER SPECIFIED STRING
		$keywords = get_option("metakeywords");
	
		//LOAD DESCRIPTION FROM BLOGINFO
		$description = get_option("metadescription");
	}
	elseif(is_page() && !is_home() && !is_front_page())
	{
		//PULL THE VALUES FROM THE SAVED SETTINGS IN THE DATABASE
		$tmpPage = get_page(get_the_ID());
		$keywords = get_option($tmpPage->ID . "_keywords");
		$description = get_option($tmpPage->ID . "_description");
	}
	elseif(is_single())
	{
		//LOAD POST TITLE AS DESCRIPTION
		$description = $post->post_title;
		
		//BUILD LIST OF KEYWORDS FROM TAGS
		$tags = wp_get_post_tags($post->ID);
		foreach($tags as $tag)
		{
			//ONLY ADD THE COMMA IF THERE ARE OTHER KEYWORDS IN FRONT OF THIS ONE
			if($keywords != "" && $keywords != NULL)
				$keywords = $keywords . ", ";
			
			//APPEND THE TAG AS A KEYWORD
			$keywords = $keywords . $tag->name;
		}
	}
	elseif(is_category())
	{
		//LOAD CATEGORY DESCRIPTION
		$tmpCategory = get_the_category();
		$keywords = single_cat_title('', false);
		$description = $tmpCategory[0]->category_description;
	}
	
	//PRINT THE VALUES TO THE HEADER SECTION
	echo "<!--Auto Keywords Generator -->\n";
	
	//IF THE USER WANTS THE AUTHOR SHOWN THEN DISPLAY THIS INFORMATION
	if(get_option("metashowauthor") && $author != "")
		echo "<meta name=\"author\" content=\"" . $author . "\" />\n";
	
	//CONTINUE TO PRINT THE REST OF THE META INFORMATION
	echo "<meta name=\"keywords\" content=\"" . str_replace("\'", "'", $keywords) . "\" />\n";
	echo "<meta name=\"description\" content=\"" . str_replace("\'", "'", $description) . "\" />\n";
	echo "<!--Auto Keywords Generator -->\n";
}

function metatagsAdminMenu()
{
	//ADD THE HANDLER FOR THE OPTIONS SCREEN
	add_options_page("Auto Keywords and Description Tags Generator Options", "Keywords and Description", "administrator", "metaOptions", "metatagsOptionsPage");
}

function metatagsOptionsPage()
{
	//USE THE VALUES FROM THE DATABASE INCASE THERE IS NOTHING BEING PASSED IN FROM THE POST
	$keywordValue = get_option("metakeywords");
	$descValue = get_option("metadescription");
	$showauthor = get_option("metashowauthor");
	
	//IF THE USER CLICKED THE SUBMIT BUTTON THEN SAVE THE SETTINGS
	if($_POST["hidSubmit"] == 'Y')
	{
		$keywordValue = $_POST["default_keywords"];
		$descValue = $_POST["default_description"];
		$showauthor = $_POST["chkshowauthor"];
		
		//UPDATE THE STATIC PAGE OPTIONS FROM WHAT THE USER SUBMITTED
		foreach(get_pages() as $tmpPage)
		{
			update_option($tmpPage->ID . "_keywords", $_POST[$tmpPage->ID . "_keywords"]);
			update_option($tmpPage->ID . "_description", $_POST[$tmpPage->ID . "_description"]);
		}
		
		//UPDATE THE DEFAULT VALUES
		update_option("metakeywords", $keywordValue);
		update_option("metadescription", $descValue);
		update_option("metashowauthor", $showauthor);
		
		//LET THE USER KNOW THE VALUES HAVE BEEN SAVED
		echo "<div class=\"updated\"><p><strong>Your changes have been saved</strong></p></div>\n";
	}
	
	//SETUP CHECK STATE FOR CHECK BOXES
	if($showauthor == "1")
		$authorChecked = "checked";
	
	//PRINT OUT JAVASCRIPT FOR HIDING AND SHOWING DIVS
	echo "<script type=\"text/javascript\">\n";
	echo "<!--\n";
	echo "	function toggleVisibility(id) {\n";
	echo " 		var e = document.getElementById(id);\n";
	echo " 		if(e.style.display == \"block\")\n";
	echo " 			e.style.display = \"none\";\n";
	echo " 		else\n";
	echo " 			e.style.display = \"block\";\n";
	echo "	}\n";
	echo "-->\n";
	echo "</script>";

	echo "<div class=\"wrap\">";
	echo "	<h3>Auto Meta Tags Generator Options</h3>";
	echo "	<form name=\"form1\" method=\"post\" action=\"\">";
	echo "		<input type=\"hidden\" name=\"hidSubmit\" value=\"Y\" />";
	echo "		<h3>Basic Information</h3>This sections handles the display options as well as the default information for your homepage.<hr/>";
	
	//OUTPUT DISPLAY OPTIONS
	echo "		<div id=\"poststuff\" class=\"metabox-holder\">";
	echo "			<div id=\"normal-sortables\" class=\"meta-box-sortables\">";
	echo "				<div class=\"postbox \">";
	echo "					<div class=\"handlediv\">";
	echo "						<br/>";
	echo "					</div>";
	echo "					<h3 class=\"hndle\" title=\"Click to toggle\"><span>Display Options</span></h3>";
	echo "					<div class=\"inside\" style=\"display:block;\">";
	echo "						<p>Display Author Name: <input type=\"checkbox\" name=\"chkshowauthor\" value=\"1\" " . $authorChecked . " /></p>";
	echo "					</div>";
	echo "				</div>";
	echo "			</div>";
	echo "		</div>";
	
	//OUTPUT HOMEPAGE INFORMATION
	echo "		<div id=\"poststuff\" class=\"metabox-holder\">";
	echo "			<div id=\"normal-sortables\" class=\"meta-box-sortables\">";
	echo "				<div class=\"postbox \">";
	echo "					<div class=\"handlediv\">";
	echo "						<br/>";
	echo "					</div>";
	echo "					<h3 class=\"hndle\" title=\"Click to toggle\"><span>Homepage Information</span></h3>";
	echo "					<div class=\"inside\" style=\"display:block;\">";
	echo "						<p>Homepage Keywords seperated by commas:<br/><textarea name=\"default_keywords\" style=\"width: 100%; height: 100px;\">" . $keywordValue . "</textarea></p>";
	echo "						<p>Homepage Description:<br/><textarea name=\"default_description\" style=\"width: 100%; height: 100px;\">" . $descValue . "</textarea></p>";
	echo "					</div>";
	echo "				</div>";
	echo "			</div>";
	echo "		</div>";	
	
	//OUTPUT STATIC PAGES
	echo "		<h3>Static Page Keywords and Descriptions</h3>Please note that if you use a static page as your home page the above keywords and description will override what you place below.<hr/>";
	foreach(get_pages('sort_column=post_title') as $tmpPage)
	{
		echo "<div id=\"poststuff\" class=\"metabox-holder\" style=\"width: 500px;\">";
		echo "	<div id=\"normal-sortables\" class=\"meta-box-sortables\">";
		echo "		<div class=\"postbox \">";
		echo "			<div class=\"handlediv\" title=\"Click to toggle\" onclick=\"toggleVisibility('div" . $tmpPage->ID . "');\">";
		echo "				<br/>";
		echo "			</div>";
		echo "			<h3 class=\"hndle\" title=\"Click to toggle\" onclick=\"toggleVisibility('div" . $tmpPage->ID . "');\"><span>" . $tmpPage->post_title . "</span></h3>";
		echo "			<div id=\"div" . $tmpPage->ID . "\" class=\"inside\" style=\"display:none;\">";
	
		echo "				<p>Keywords seperated by commas:<br/><textarea name=\"" . $tmpPage->ID ."_keywords\" style=\"width: 100%; height: 100px;\">" . str_replace("\'", "'", get_option($tmpPage->ID . "_keywords")) . "</textarea></p>";
		echo "				<p>Description:<br/><textarea name=\"" . $tmpPage->ID . "_description\" style=\"width: 100%; height: 100px;\">" . str_replace("\'", "'", get_option($tmpPage->ID . "_description")) . "</textarea></p>";
	
		echo "			</div>";
		echo "		</div>";
		echo "	</div>";
		echo "</div>";
	}
	
	//OUTPUT CLOSING AND SUBMIT BUTTON
	echo "		<p class=\"submit\">";
	echo "			<input type=\"submit\" name=\"Submit\" value=\"Update Options\"/>";
	echo "		</p>";
	echo "	</form>";
	echo "</div>";

}

//ADD THE TWO DEFAULT OPTIONS WITH BLANK VALUES
add_option("metakeywords", "");
add_option("metadescription", "");

//REGISTER ADMIN MENU OPTION
add_action("admin_menu", "metatagsAdminMenu");

//REGISTER WORDPRESS_HEAD HOOK
add_action("wp_head", "generateMetaTags");
?>