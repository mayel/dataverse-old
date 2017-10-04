<?php
require_once('../config/secrets.php');
header('Content-Type: application/javascript');
?>

function load_form() {
	$('<link>').attr('rel','stylesheet')
	  .attr('type','text/css')
	  .attr('href',"<?=$bv->config->home_url?>css/embed.css")
	  .appendTo('head');
	  
	$('body').append(
	  $('<div/>')
	    .attr("id", "form_black_out")
	);
	$('body').append(
	  $('<div/>')
	    .attr("id", "form_contain")
	);
	$('#form_contain').append(
	  $('<div/>')
	    .attr("id", "form_loader")
	);
	$('#form_contain').append(
	  $('<div/>')
	    .attr("id", "form_contain_embed")
	);
	$('#form_contain_embed').append(
	  $('<iframe/>')
	);
    $('#form_contain_embed iframe').attr('src', "<?=$bv->config->home_url?>question?embedded&questionnaire=<?=$_GET['id']?>&after=0");
    $('#form_contain_embed iframe').on('load', function() {
        $('#form_loader').fadeOut(function() {
            $('#form_contain_embed').fadeIn();
        });
    });
}