<template>
    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="navbar-header">
            <a class="navbar-brand" href="/">
                <img class="smartadmin-logo" style="display: inline;" src="/resources/images/static_sites/og-login-logo.png" width="26">
                Smartbackend
            </a>
        </div>
        <div class="collapse navbar-collapse show_notification_data" id="bs-navbar-collapse-1">
            <div class="container-fluid">
                <!-- <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="glyphicon glyphicon-globe"></i>
                            <span class="approved_thirdparty_count badge notification_count">
                                0
                            </span>
                            <span class="caret"></span>
                        </a>
                        <ul id="approved_thirdparty_count" class="dropdown-menu user-option" role="menu">
                            <li>
                                <a href="/">
                                    <span style="color:black;">Messages</span>
                                    <span class="promo-count-dropdown badge notification_count">0</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a id="user-link" href="" class="dropdown-toggle" data-toggle="dropdown">
                            admin <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="/user_management/viewUserSetting">
                                    <i class="glyphicon glyphicon-cog"></i> 
                                    Settings
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a id="logout" href="/auth/logout" onclick="return confirm('Are you sure')">
                                <i class="glyphicon glyphicon-off"></i> Logout</a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <div class="navbar-form navbar-right">
                    <div class="form-group">
                        <input id="gotomemberinfo" type="text" class="form-control input-sm" placeholder="username">
                    </div>
                    <div class="form-group">
                        <select name="language" id="lang_select" class="form-control input-sm" style="background-color:#ffffff; color:#6f6f6f">
                            <option value="1">English</option>
                            <option value="2">中文</option>
                            <option value="3">Indonesian</option>
                            <option value="4">Vietnamese</option>
                            <option value="5">Korean</option>
                            <option value="6">Thai</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="_select_currecny_on_logged form-control input-sm user-success">
                            <option value="super">All</option>
                            <option value="thb" selected>฿ THB</option>
                            <option value="idr">IDR IDR</option>
                        </select>
                    </div>
                </div> -->
            </div>
            <nav id="navbar-customized-focus" class="navbar-inverse" style="margin-bottom: 0; margin-left: -15px;">
                <div class="container-fluid">
                    
                    <ul class="nav navbar-nav">
                        <li class="">
                            <a href="/player_management/viewAllPlayer">Player</a>
                        </li>
                        <li class="">
                            <a href="/cs_management/messages">CS</a>
                        </li>
                        <li class="">
                            <a href="/home/nav/deposit_all">Payment</a>
                        </li>
                        <li class="">
                            <a href="/marketing_management/promoRuleManager">Marketing</a>
                        </li>
                        <li class="">
                            <a href="/cms_management/viewNews">CMS</a>
                        </li>
                        <li class="">
                            <a href="/affiliate_management/viewDomain">Affiliate</a>
                        </li>
                        <li class="">
                            <a href="/report_management/summary_report_2">Report</a>
                        </li>
                        <li class="">
                            <a href="/user_management/viewUsers">System</a>
                        </li>
                        <li class="">
                            <a href="/agency_management/agency_logs">Agency</a>
                        </li>
                        <li class="">
                            <a href="/theme_management">Theme</a>
                        </li>
                        <li class="active">
                            <a href="/vue_report_management/quickFireReport">Vue Report</a>
                        </li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right hidden-sm">
                        <li>
                            <a href="http://www.gamegateway.t1t.games/pdf/SmartbackendUserGuide_2.8.22.1001.pdf" target="_blank" >
                                <i class="glyphicon glyphicon-question-sign text-warning"></i>
                                <span id="hide_text" >
                                    Guide
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </nav>
</template>