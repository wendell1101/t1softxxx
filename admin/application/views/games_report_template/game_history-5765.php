<?php
$_data = json_decode($data, true);
?>
<span class="game_history_sport">Sport: <?php echo $this->CI->utils->extractLangFromData($_data, 'sport_type'); ?></span><br>
<span class="game_history_sport">Win Lose Status: <?php echo $this->CI->utils->extractLangFromData($_data, 'win_lose_status'); ?></span><br>
<span class="game_history_sport">Bet Type: <?php echo $this->CI->utils->extractLangFromData($_data, 'bet_type'); ?></span><br>
<span class="game_history_sport">Odds Type: <?php echo $this->CI->utils->extractLangFromData($_data, 'odds_type'); ?></span><br>
<?php
if(isset($_data['parlay_data']) && !empty($_data['parlay_data'])){
    foreach($_data['parlay_data'] as $parlay){
?>
<hr>
<span class="game_history_league">League: <?php echo $this->CI->utils->extractLangFromData($parlay, 'leaque_name'); ?></span><br>
<span class="game_history_vs">Home Team: <?php echo $this->CI->utils->extractLangFromData($parlay, 'home_team_name'); ?></span><br>
<span class="game_history_vs">Away Team: <?php echo $this->CI->utils->extractLangFromData($parlay, 'away_team_name'); ?></span><br>
<span class="game_history_vs">HomeScore: <?php echo $this->CI->utils->extractLangFromData($parlay, 'HomeScore'); ?></span><br>
<span class="game_history_vs">AwayScore: <?php echo $this->CI->utils->extractLangFromData($parlay, 'AwayScore'); ?></span><br>
<span class="game_history_vs">FTScore: <?php echo $this->CI->utils->extractLangFromData($parlay, 'FTScore'); ?></span><br>
<span class="game_history_vs">HTScore: <?php echo $this->CI->utils->extractLangFromData($parlay, 'HTScore'); ?></span><br>
<span class="game_history_vs">Parlay Odds: <?php echo $this->CI->utils->extractLangFromData($parlay, 'ParOdds'); ?></span><br>
<span class="game_history_vs">IsBetHome: <?php echo $this->CI->utils->extractLangFromData($parlay, 'IsBetHome'); ?></span><br>
<span class="game_history_vs">IsHomeGive: <?php echo $this->CI->utils->extractLangFromData($parlay, 'IsHomeGive'); ?></span><br>
<span class="game_history_vs">ParTransType: <?php echo $this->CI->utils->extractLangFromData($parlay, 'ParTransType'); ?></span><br>
<span class="game_history_vs">IsFH: <?php echo $this->CI->utils->extractLangFromData($parlay, 'IsFH'); ?></span><br>

<?php
    }
}else{
?>
<hr>
<span class="game_history_league">League: <?php echo $this->CI->utils->extractLangFromData($_data, 'leaque_name'); ?></span><br>
<span class="game_history_vs">Home Team: <?php echo $this->CI->utils->extractLangFromData($_data, 'home_team_name'); ?></span><br>
<span class="game_history_vs">Away Team: <?php echo $this->CI->utils->extractLangFromData($_data, 'away_team_name'); ?></span><br>
<span class="game_history_vs">HomeScore: <?php echo $this->CI->utils->extractLangFromData($_data, 'HomeScore'); ?></span><br>
<span class="game_history_vs">AwayScore: <?php echo $this->CI->utils->extractLangFromData($_data, 'AwayScore'); ?></span><br>

<?php
}
?>

