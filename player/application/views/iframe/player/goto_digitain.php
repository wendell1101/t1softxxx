<!-- <!DOCTYPE html> -->
<html>
<head>
    <title><?php echo $title; ?></title>
    <script src="<?php echo $hostname; ?>/js/Partner/IntegrationLoader.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let parents = "<?php echo isset($parent_domains) ? $parent_domains : ""; ?>";
            let p_array = parents.split(",");
            var sp = [
                ["server", "<?php echo $server; ?>"+"/"],
                ["token", "<?php echo $token; ?>"],
                ["currentPage", "<?php echo $currentPage; ?>"],
                ["language", "<?php echo $language; ?>"],
                ["oddsFormat", "<?php echo isset($oddsFormat) ? $oddsFormat : ""; ?>"],
                ["oddsFormatList", "<?php echo isset($oddsFormatList) ? $oddsFormatList : ""; ?>"],
                ["sportsBookView", "<?php echo isset($sportsBookView) ? $sportsBookView : ""; ?>"],
                ["theme", "<?php echo isset($theme) ? $theme : ""; ?>"],
                ["parent", p_array],
                ["fixedHeight", true],
                ["sportPartner", "<?php echo $sportPartner; ?>"],
            ];
            SportFrame.frame(sp);
        });
    </script>
</head>
<body>
    <div id="sport_div_iframe"></div>
</body>
</html>