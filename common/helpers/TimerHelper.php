<?php
/**
 * Класс для замера производительности частей программы
 */

namespace common\helpers;

class TimerHelper{
	static $_timer_blocks = array(); 
	static $_timer_history = array();

    /**
     * @param string $msg
     */
	public static function start($msg = ''){
		self::$_timer_blocks[] = array(microtime(), $msg);
	}

    /**
     * @param string $msg
     * @return string
     */
	public static function stop($msg = ''){
		$last = array_pop(self::$_timer_blocks);
		$_start = $last[0];
		$_msg = $last[1];
		list($a_micro, $a_int) = explode(' ', $_start);
		list($b_micro, $b_int) = explode(' ', microtime());
		$elapsed = ($b_int - $a_int) + ($b_micro - $a_micro);
		self::$_timer_history[] = array($elapsed, $_msg, "$_msg [" . round($elapsed, 6) . "s]" );
		if($msg){
			return "$_msg: {$elapsed}s \n";	
		}
		return $elapsed;		
	}

    /**
     * @param string $msg
     * @return string
     */
	public static function stopAll($msg=''){
		$result = '';
		while(!empty(self::$_timer_blocks)){
			$result .= self::stop($msg);
		}
		return $result;
	}

    /**
     * @param int $inline
     * @return string
     */
	public static function listAll($inline=0){
		$o = '';
		foreach(self::$_timer_history as $mark){
			$o .= $mark[2] . ($inline?'|':" \n");
		}
		return $o;
	}		
}
?>