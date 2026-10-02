<?php

class VanaTime {

    private $elementalDay =     ["Firesday",    "Earthsday",        "Watersday",        "Windsday",         "Iceday",           "Lightningday",     "Lightsday",    "Darksday"];
    private $moonPhaseName =    ["New Moon",    "Waxing Crescent",  "First Quarter",    "Waxing Gibbous",   "Full Moon",        "Waning Gibbous",   "Last Quarter", "Waning Crescent"];
    private $dayColor =         ["#FF0000",     "#80581C",          "#0000DD",           "#00AA22",         "#7799FF",          "#AA00AA",          "#888888",      "#333333"];
    private $moonIcon =         ["\u{1F311}",   "\u{1F312}",        "\u{1F313}",        "\u{1F314}",        "\u{1F315}",        "\u{1F316}",        "\u{1F317}",    "\u{1F318}"];

    private $VTIME_BIRTH = 1024844400000;
    private $VTIME_BASEDATE  = 1009810800;
    private $vanaBirthday = (((898 * 360) + 30) * 24 * 60 * 60) / (25 / 1000); // 1117359360000 - in earth time milliseconds
    private $difference = 92514960000; //$this->vanaBirthday - $this->VTIME_BIRTH;

    // Conversions in Minutes
    private $VTIME_YEAR  =      518400;   // 360 * GameDay
    private $VTIME_MONTH =      43200;      // 30 * GameDay
    private $VTIME_WEEK  =      11520;      // 8 * GameDay
    private $VTIME_DAY   =      1440;       // 24 hours * GameHour
    private $VTIME_HOUR  =      60;         // 60 minutes
    private $VMULTIPLIER =      25;
    private $MOON_CYCLE_DAYS =  84;

    // @var DateTimeZone|null Cached result of resolveTimeZone()
    private $resolvedTimeZone = null;

    // Name of the timezone cookie
    const TIMEZONE_COOKIE = 'timezone';

    /**
     * Inline script that stores the browser's timezone in a cookie and reloads
     * ONCE so the server can render earth times in the visitor's timezone.
     */
    const TIMEZONE_SYNC_JS = <<<'JS'
(function () {
    var COOKIE = 'timezone';
    function readCookie() {
        var parts = document.cookie.split(';');
        for (var i = 0; i < parts.length; i++) {
            var c = parts[i].replace(/^\s+/, '');
            if (c.indexOf(COOKIE + '=') === 0) {
                try { return decodeURIComponent(c.substring(COOKIE.length + 1)); } catch (e) { return ''; }
            }
        }
        return '';
    }
    var tz;
    try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) { return; }
    if (!tz || readCookie() === tz) { return; }
    document.cookie = COOKIE + '=' + encodeURIComponent(tz) +
        ';path=/;max-age=' + (86400 * 30) + ';samesite=lax;secure';
    if (readCookie() === tz) {
        location.reload();
    }
})();
JS;

    // Put the timezone-sync script in <head>.
    public static function addTimezoneSync( OutputPage $out ) {
        $out->addHeadItem( 'ffxiph-timezone-sync', Html::inlineScript( self::TIMEZONE_SYNC_JS ) );
    }

    // The visitor's timezone: the cookie set by TIMEZONE_SYNC_JS, otherwise the site's local timezone.
    public function resolveTimeZone() {
        if ( $this->resolvedTimeZone !== null ) {
            return $this->resolvedTimeZone;
        }
        $name = isset( $_COOKIE[self::TIMEZONE_COOKIE] ) ? $_COOKIE[self::TIMEZONE_COOKIE] : '';
        if ( is_string( $name ) && $name !== '' ) {
            try {
                $this->resolvedTimeZone = new DateTimeZone( $name );
                return $this->resolvedTimeZone;
            } catch ( Exception $e ) {
                // fall through to the default below
            }
        }
        $this->resolvedTimeZone = new DateTimeZone( date_default_timezone_get() );
        return $this->resolvedTimeZone;
    }

    /**
     *  All forms of time are derived from this.
     *  Integer - represented in Vana'diel MINUTES
     */
    public function _vTime(){
        $earthTime = floor( floor(microtime(true) * 1000)  / 1000 );
        return (($earthTime - $this->VTIME_BASEDATE) / 60 * 25) + 886 * $this->VTIME_YEAR;
    }

    public function _vTimeBeginningOfCurrentHour(){
        $earthTime = floor( floor(microtime(true) * 1000)  / 1000 );
        //$earthTime = ( $earthTime - ( $earthTime % (24 * 60  ) ));
        $vt = (int)(($earthTime - $this->VTIME_BASEDATE) / 60 * 25) + 886 * $this->VTIME_YEAR;
        return  ( $vt - ( $vt % ( 24 * 60)));
    }

    /**
     *  Only used to calculate - getWeatherDate()
     *  Should not otherwise be used.
     */
    public function _eTime(){
        return floor( floor(microtime(true) * 1000)  / 1000 );
    }

    /**
     *  Integer - Vana'diel Year
     *  Verified
     */
    public function year($vanatime) {
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();
        return floor($vanatime / $this->VTIME_YEAR);
    }

    public function today_inMS(){ // result in earth Milliseconds
        $now = ((898 * 360 + 30) * (24 * 60 * 60 * 1000)) + (floor(microtime(true)) - $this->VTIME_BIRTH) * $this->VMULTIPLIER;
        return ( $now - ( $now % (24 * 60 * 60 * 1000) ));
    }

    // public function now_inEarthMS(){
    //     return ((898 * 360 + 30) * (24 * 60 * 60 * 1000)) + (floor(microtime(true)) - $this->VTIME_BIRTH) * $this->VMULTIPLIER;
    // }

    // public function now(){ return $this->now_inEarthMS() / ( 60 * 1000); }

    public function getWeatherDate(){
        return intval((($this->_eTime() - $this->VTIME_BASEDATE) / 3456)) % 2160 ;
    }

    /**
     *
     */
    public function getVanaTimeFromDaysAhead($daysAhead){
        return (int)$this->_vTimeBeginningOfCurrentHour() + ($daysAhead * $this->VTIME_DAY); 
    }

    public function weekDayFrom($daysAhead){
        //print_r("<br/>" . ((int)$this->_vTime() % $this->VTIME_WEEK) + ( $daysAhead * $this->VTIME_DAY));
        return floor(( ( (int)$this->_vTime() + ($daysAhead * $this->VTIME_DAY)) % $this->VTIME_WEEK)  / $this->VTIME_DAY);
    }

    public function weekDay($vanatime){
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();
        return floor(((int)$vanatime % $this->VTIME_WEEK) / $this->VTIME_DAY);
    }

    public function getWeekDayElement($vanaWeekDay){
        return $this->elementalDay[$this->weekDayFrom($vanaWeekDay)];
    }

    public function dayColor($vanatime){
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();
        return $this->dayColor[$this->weekDay($vanatime)];
    }

    public function moonLatentPhase($vanatime, $daysAhead){
        if ( !isset($daysAhead) ) $daysAhead = 0;
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();
        $vanatime = $vanatime + ($daysAhead * $this->VTIME_DAY);

        $moonPhase = $this->moonPercent($vanatime);
        $moonDirection = $this->moonDirection($vanatime);

        if ($moonPhase <= 5 || ($moonPhase <= 10 && $moonDirection == 1)) {return 0;} // New Moon - 10% waning -> 5% waxing
        else if ($moonPhase >= 7 && $moonPhase <= 38 && $moonDirection == 2) {return 1;}  // Waxing Crescent - 7% -> 38% waxing
        else if ($moonPhase >= 40 && $moonPhase <= 55 && $moonDirection == 2){return 2;}  // First Quarter - 40%% -> 55% waxing
        else if ($moonPhase >= 57 && $moonPhase <= 88 && $moonDirection == 2){return 3;}  // Waxing Gibbous - 57% -> 88%
        else if ($moonPhase >= 95 || ($moonPhase >= 90 && $moonDirection == 2)){return 4;}  // Full Moon - waxing 90% -> waning 95%
        else if ($moonPhase >= 62 && $moonPhase <= 93 && $moonDirection == 1){return 5;}  // Waning Gibbous - 93% -> 62%
        else if ($moonPhase >= 45 && $moonPhase <= 60 && $moonDirection == 1){return 6;}  // Last Quarter - 60% -> 45%
        else{return 7;}  // Waning Crescent - 43% -> 12%
    }

    public function moonDirection($vanatime){
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();
            $moondays = floor($this->moonDays($vanatime));
            //console.log(daysmod);
            if ($moondays == 42 || $moondays == 0) { return 0; }// neither waxing nor waning
            else if ($moondays < 42){ return 1; } // waning
            else{ return 2; } // waxing
        }

    public function moonDays($vanatime){
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();
        return  ( (int)(( $vanatime /  $this->VTIME_DAY ) + 26) % $this->MOON_CYCLE_DAYS);
    }

    public function moonPercent($vanatime){
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();
        return abs( round((42 - floor($this->moonDays($vanatime))) / 42 * 100) );
    }

    public function moonPhaseNameFrom($daysAhead){
        $vt = $this->getVanaTimeFromDaysAhead($daysAhead);
        $moonday = $this->moonLatentPhase($vt, null);
        return $this->moonPhaseName[$moonday] . " " . $this->moonPhaseIcon($moonday) . $this->moonPercent($vt) . "%";
    }

    public function moonPhaseIcon($day){
        if (!isset($day) ) return $this->moonIcon[$this->moonLatentPhase(null, null)];
        else return $this->moonIcon[$day];
    }

    public function earthTime($vanatime){
        if ( !isset($vanatime) ) $vanatime = $this->_vTime();

        /**
         * Should be backwards from this
         * $vanatime = (($earthTime - $this->VTIME_BASEDATE) / 60 * 25) + 886 * $this->VTIME_YEAR;
         * $vanatime - (886 * $this->VTIME_YEAR) = (($earthTime - $this->VTIME_BASEDATE) / 60 ) * 25
         * (( $vanatime - (886 * $this->VTIME_YEAR) ) / 25 ) = ($earthTime - $this->VTIME_BASEDATE) / 60
         * ((( $vanatime - (886 * $this->VTIME_YEAR) ) / 25 ) * 60) = ($earthTime - $this->VTIME_BASEDATE)
         * ((( $vanatime - (886 * $this->VTIME_YEAR) ) / 25 ) * 60) + $this->VTIME_BASEDATE = $earthTime
         * 
         * $earthTime = floor( floor(microtime(true) * 1000)  / 1000 );
         * floor (floor ( (($vanatime - (886 * $this->VTIME_YEAR) ) / 25 * 60 + $this->VTIME_BASEDATE) * 1000) / 1000 )
         * 
         **/
        
         $et = floor( floor(microtime(true) * 1000)  / 1000 );
         //$test =  (int)((($et - $this->VTIME_BASEDATE) / 60) * 25) + ( 886 * $this->VTIME_YEAR );
         $test = ((( $vanatime - (886 * $this->VTIME_YEAR) ) / 25 ) * 60) + $this->VTIME_BASEDATE;


         //print_r("<br/>et: " . $et . " vt: " . $test);

        // $vanatime = $vanatime / ( $this->VMULTIPLIER );
        // $vTempTime = $this->today_inMS() / (60 * 1000);
        // $vTempTime = $vTempTime * 60;
        // $vTempTime = floor($vTempTime / (25 / 1000)) - $this->difference;
        $vTempTime =   (int)(((($vanatime / $this->VTIME_YEAR) - 886 / 25 ) * 60 ) + $this->VTIME_BASEDATE) ;


        //print_r("<br/>" . $vTempTime . "  " . (int)$vanatime);

        $dt = new DateTime("now", $this->resolveTimeZone());
        //$dt->setTimestamp(floor((int)$vanatime) - ($this->vanaBirthday - $this->VTIME_BIRTH));
        $dt->setTimestamp( $test );
        return $dt->format("d-M h:i A");
    }


}

 ?>
