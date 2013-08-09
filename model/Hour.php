<?php
class Hour extends Illuminate\Database\Eloquent\Model
{
	function user()
	{
		$this->belongsTo('User');
	}
}
?>