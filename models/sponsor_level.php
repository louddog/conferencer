<?php

new Conferencer_Sponsor_Level();
class Conferencer_Sponsor_Level extends Conferencer_CustomPostType {
	var $slug = 'sponsor_level';
	var $archive_slug = 'sponsor-levels';
	var $singular = "Sponsor Level";
	var $plural = "Sponsor Levels";
}