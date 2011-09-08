<?php

new Conferencer_Company();
class Conferencer_Company extends Conferencer_CustomPostType {
	var $slug = 'company';
	var $archive_slug = 'companies';
	var $singular = "Company";
	var $plural = "Companies";
}