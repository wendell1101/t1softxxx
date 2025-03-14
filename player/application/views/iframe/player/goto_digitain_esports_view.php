<!-- <!DOCTYPE html> -->
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
</head>
<body>
    <div id="application-container"></div>
    <script src="<?php echo $hostname; ?>/js/partner/bootstrapper.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let parent_domains = "<?php echo isset($parent_domains) ? $parent_domains : ""; ?>";
            let parents = parent_domains.split(",");
            var params = {
                /* Required parameters */
                // {String} The sport URL provided by Digitain.
                 server: "<?php echo $server; ?>",
                // {String} DOM element Id where application will be rendered.
                containerId: "application-container",
                /* Optional parameters */
                // {String} User authorization token or '-' for unauthorized users.
                token: "<?php echo $token; ?>",
                // {String} The default language ISO code.
                defaultLanguage: "<?php echo $language; ?>",
                // {Function} Login popup/page opening trigger.
                loginTrigger: function() {
                openSomeLoginModal();
                },
                 /**
                 * When application starts, it calls `onUniqueIdChange` with generated random uuid,
                 * after bet is success uuid is regenerated and application calls “onUniqueIdChange” 
                 * with newly generated uuid.
                 */
                 onUniqueIdChange: uuid => {
                 console.log({ uuid }); 
                 },
                // {Boolean} Disables hash router to prevent main URL from changing on routes navigation. 
                // * useful in case of integrating into existing SPA.
                hashRouterDisabled: false,
                 /**
                 * theme – a parameter indicating the default theme for ESport.
                 * The possible value of the parameter is either dark or light.
                 * The default theme is dark.
                 */
                theme: "<?php echo isset($theme) ? $theme : ""; ?>",
                 /**
                 * Array of odds format in the dropdown to be shown. Possible values: 0- decimal,
                 * 1 - Fractional; 2 - American; 3 - Hong Kong; 4 - Malay; 5 – Indo. If the value of
                 * the oddsformatList is not set, or an empty array is received, all the odd formats
                 *
                 * * will be shown.
                 */
                 oddsFormatList: [0, 1, 2],
                 /**
                 * Indicates default odds format. Possible values of the paramater: 0- decimal 
                 * 1 - Fractional; 2 - American; 3 - Hong Kong; 4 - Malay; 5 – Indo. If the default
                 * value of the oddsFormat parameter is not set, the first value of the
                 * oddsFormatList will be taken. And, if the value of the oddsFormatList is not
                 * set, the default value of the oddsformat parameter will be taken.
                 */
                 oddsFormat: 2,
                 /**
                 * Mandatory Javascript parameter provided by the partner to send the list of
                 * domain(s) indicating where the iFrame is embedded currently.
                 * The partner must have one parent key for each domain the site of the partner uses
                 */
                 parent: parents,

                /**
                * Identifier of the partner provided by Digitain.
                */
                sportPartner: "<?php echo $sportPartner; ?>"
            };



            /**
            * Native integration
            *
            * {Object} parameters, {Object} app config
            */
            Bootstrapper.boot(params, { name: "ESport" });
        });
    </script>
</body>
</html>