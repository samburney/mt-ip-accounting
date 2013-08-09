<?php
class RawHour extends Illuminate\Database\Eloquent\Model
{
	protected $fillable = array('date', 'src_addr', 'dst_addr', 'bytes', 'packets', 'src_user_name', 'dst_user_name');
}
?>