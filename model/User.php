<?php
class User extends Illuminate\Database\Eloquent\Model
{
	function hours()
	{
		return $this->hasMany('Hour');
	}

	function raw_hours()
	{
		return $this->hasMany('RawHour');
	}
}
?>