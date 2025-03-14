<template>
    <div>
        <vue-report-navbar />
        <vue-report-sidebar />
        <div id="page-content-wrapper">
            <span id="sidebar_status" class="active"></span>
            <div id="wrapper" class="active">  
                <div id="main_content">
                    <!-- welcome -->
                    <router-view />
                </div>  
            </div>
        </div>
    </div>
</template>
<script>
import VueReportNavbar from './VueReportNavbar.vue';
import VueReportSidebar from './VueReportSidebar.vue';


export default {
  name: 'HomeViewReport',
  components: {
    VueReportNavbar,
    VueReportSidebar,
  },
};
</script>