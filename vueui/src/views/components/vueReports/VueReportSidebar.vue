<template>
    <!-- /.container -->
    <div id="sidebar-wrapper" class="inactive">
        <ul class="sidebar-nav" id="sidebar">
            <!-- <li>
                <router-link to="/vue_report" class="list-group-item" id="view_registration_setting" style="border: 0px;margin-bottom:0.1px;" href="/" data-toggle="tooltip" data-placement="right" title="Home">
                    <i class="icon-pie-chart" id="icon"></i>
                    <span id="hide_text">
                        Home
                    </span>
                </router-link>
            </li> -->
            <li>
                <router-link to="/vue_report/quickfire" class="list-group-item" id="view_registration_setting" style="border: 0px;margin-bottom:0.1px;" href="/" data-toggle="tooltip" data-placement="right" title="Quick Fire Report">
                    <i class="icon-pie-chart" id="icon"></i>
                    <span id="hide_text">
                        Quick Fire Report
                    </span>
                </router-link>
            </li>
        </ul>
        <ul id="sidebar_menu" class="sidebar-nav">
            <li class="sidebar-brand">
                <a id="menu-toggle" href="#" onclick="">
                    <span id="main_icon" class="icon-arrow-left pull-right"></span>
                </a>
            </li>
        </ul>
    </div>
</template>