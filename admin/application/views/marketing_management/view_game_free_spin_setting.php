<style>
    .game-providers {
        display: grid;
        grid-template-columns: repeat(6,1fr);
        grid-gap: 20px;
        margin: 25px;
    }
    .game-item {
        text-align: center;
        border: 1px solid #7db3d9;
        border-radius: 4px;
        overflow: hidden;
        position: relative;
        height: 115px;
        padding-top: 10px;
    }
    .game-item a {
        display: block;
        position: relative;
        height: 100%;
    }
    .game-item a img {
        height: 70px;
    }
    .game-item a p {
        position: absolute;
        margin: 0;
        background-color: #7db3d9;
        border: 1px solid #7db3d9;
        color: #fff;
        padding: 5px;
        text-decoration: none;
        width: 100%;
        left: 0;
        bottom: 0;
    }
</style>
<div class="panel panel-primary">
	<div class="game-providers">
		<?php
            if (!empty($games)) {
                foreach ($games as $game) { 
        ?>
	        <div class="game-item">
	            <a href="/marketing_management/view_game_campaign/<?= $game['id'] ?>">
	                <img class="provider-logo" src="<?=$this->utils->processAnyUrl('campaign_logo/'.$game["logo"], '/resources/images')?>" alt="">
	                <p class="provider-name"><?= $game['name'] ?></p>
	            </a>
	        </div>
        <?php
                }
            }
        ?>
    </div>
</div>