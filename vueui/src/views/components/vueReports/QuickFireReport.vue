<template>
  <div class="">
    <div class="panel panel-primary">
      <div class="panel-heading">
          <h4 class="panel-title">
              <i class="fa fa-search"></i> MG Quick Fire Player Login Sessions {{ generateTitle }}
          </h4>
      </div>
      <div class="panel-body">
        <form id="form-filter" class="form-horizontal" method="GET" onsubmit="return validateForm();">
          <div class="row">
              <div class="col-md-2 col-md-offset-2 text-right">
                <label class="control-label" for="group_by"><strong>Show players session of</strong></label>
              </div>
              <div class="col-md-2">
                <input class="form-control dateInput" id="datetime_range" data-start="#datetime_from" data-end="#datetime_to" data-time="false" />
                <input type="hidden" id="datetime_from" name="datetime_from"  v-model="dateFrom"/>
                <input type="hidden" id="datetime_to" name="datetime_to"  v-model="dateTo" />
              </div>
              <div class="col-md-1">
                <!-- <div class="btn-group-toggle" data-toggle="buttons">
                  <label class="btn btn-block btn-default active">
                    <input type="checkbox" checked autocomplete="off" class=""> Filter
                  </label>
                </div> -->
                <!-- <input type="button" value="Filter" class="btn btn-block btn-default" v-on:click="filter = !filter" > -->
                <button type="button" class="btn btn-block btn-default" v-on:click="toggleFilter" >{{buttonFilter.text}}</button>
              </div>
              <div class="col-md-1">
                <input type="button" value="Search" class="btn btn-block btn-primary" @click="searchTransaction">
              </div>
          </div>
          <br>
          <div class="col-md-4 col-md-offset-4" v-if="filter">
            <div class="panel panel-primary" >
              <div class="panel-body" >
                <div class="row">
                  <div class="col-md-3 text-right">
                    <label class="control-label" for="group_by"><strong>Username : </strong></label>
                  </div>
                  <div class="col-md-8">
                    <input type="text" class="form-control" id="usr" v-model="username">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="panel panel-primary" >
      <div class="panel-heading">
          <h4 class="panel-title"><i class="fa fa-pie-chart"></i> PERIOD SUMMARY</h4>
      </div>
      <div class="panel-body" >
        <div class="row">
          <div class="col-md-3"><strong>Total Count : </strong> {{ summary.count }}</div>
            <div class="col-md-3"><strong>Total Wagered : </strong> {{ summary.totalWagered }}</div>
            <div class="col-md-3"><strong>Total Payout : </strong> {{ summary.totalPayout }}</div>
            <div class="col-md-3"><strong>Win/Loss Total : </strong> {{ summary.totalWinloss }}</div>
        </div>
      </div>
    </div>
    <div class="panel panel-primary" >
      <div class="panel-body vld-parent" style="position:relative;" ref="panelContainer">
        <!-- <div class="pre-loader" style="position: absolute;width: 100%;height: 100%;left: 0;top: 0;padding: 15px 15px 75px 15px;">
          <div class="loader-backdrop" style="position: relative;height: 100%;background: rgba(28, 56, 93, 0.9);">
            <div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
          </div>
        </div> -->
        <!-- {{ transactions }} -->
        <table class="table table-bordered table-hover " id="mg_quick_table" style="min-height:100px;">
          <thead>
            <tr>
                <th>TRANSACTION</th>
                <th>SESSION</th>
                <th>TIME</th>
                <th>DESCRIPTION</th>
                <th>WAGERED</th>
                <th>PAYOUT</th>
                <th>WIN/LOSS</th>
                <th>USERNAME</th>
            </tr>
          </thead>
          <tbody>
            <tr style="text-align:center;vertical-align:middle" v-if="transactions.length === 0">
              <td colspan="8">No results</td>
            </tr>
            <tr v-for="transaction in transactions" :key="transaction.transaction">
              <td>{{ transaction.transaction }}</td>
              <td>{{ transaction.session }}</td>
              <td>{{ transaction.time }}</td>
              <td>{{ transaction.description }}</td>
              <td>{{ transaction.wagered }}</td>
              <td>{{ transaction.payout }}</td>
              <td>{{ transaction.win_loss }}</td>
              <td>{{ transaction.username }}</td>
            </tr>
          </tbody>
          <tfoot>
          </tfoot>
        </table>
        <div>
          <vue-report-pagination 
            :max-visible-buttons="summary.maxVisibleButtons"
            :total-pages="summary.totalPages"
            :total="summary.total"
            :per-page="summary.perPage"
            :current-page="currentPage"
            @pagechanged="onPageChange"
          />
          <!-- <ul class="pagination pull-right" >
            <li class="disabled"><a href="#" v-on:click.prevent.self>First</a></li>
            <li><a href="#" v-on:click.prevent.self>Previous</a></li>
            <li class="active"><a href="#" v-on:click.prevent.self>1</a></li>
            <li><a href="#" v-on:click.prevent.self>2</a></li>
            <li><a href="#" v-on:click.prevent.self>3</a></li>
            <li><a href="#" v-on:click.prevent.self>Next</a></li>
            <li><a href="#" v-on:click.prevent.self>Last</a></li>
          </ul> -->
          <!-- <ul class="pagination pull-right" v-html="links"></ul> -->
        </div>
      </div>
      <!-- <div class="panel-footer"></div> -->
    </div>
  </div>
</template>
<script>
import axios from 'axios';
import VueReportPagination from './VueReportPagination.vue';
/*eslint-env jquery*/
/* eslint no-console: ["error", { allow: ["warn", "error"] }] */
/*eslint no-alert: "error"*/
export default {
  data: function () {
    return {
      currentPage: 1,
      transactions: [],
      title: null,
      username: null,
      filter: false,
      buttonFilter: {
        text: 'Filter',
      },
      dateFrom: new Date().toISOString().slice(0,10),
      dateTo: new Date().toISOString().slice(0,10),
      summary: {
        totalWagered: 0,
        totalPayout: 0,
        totalWinloss: 0,
        count: 0,
        perPage: 0,
        totalPages: 0,
        total: 0,
      },
    };
  },
  components: {
    VueReportPagination,
  },
  methods: {
    toggleFilter: function () {
      this.filter = !this.filter;
      this.buttonFilter.text = !this.filter ? 'Filter' : 'Remove filter';
    },
    searchTransaction: function () {
      let loader = this.$loading.show({
        container: this.$refs.panelContainer,
      });

      this.currentPage = 1;
      this.dateFrom = $('#datetime_from').val();
      this.dateTo = $('#datetime_to').val();
      let currentObj = this;
      axios
        .post('/api/getMgQuickFireReport', {
          params: {
            username: this.username,
            dateFrom: this.dateFrom,
            dateTo: this.dateTo,
          },
        })
        .then(function (response) {
          loader.hide();
          currentObj.links = response.data.links;
          currentObj.summary = response.data.summary;
          setTimeout(() => currentObj.transactions = response.data.transactions, 100);
        })
        .catch(function (error) {
          currentObj.searchOutput = error;
        });
    },
    onPageChange(page) {
      let loader = this.$loading.show({
        container: this.$refs.panelContainer,
      });
      this.currentPage = page;
      axios
        .post('/api/getMgQuickFireReport/'+ `${this.currentPage}`, {
          params: {
            username: this.username,
            dateFrom: this.dateFrom,
            dateTo: this.dateTo,
          },
        })
        .then(
          response => (
            loader.hide(),
            this.links = response.data.links,
            this.summary = response.data.summary,
            this.transactions = response.data.transactions
          )
        );
    },
  },
  mounted(){
    let loader = this.$loading.show({
      container: this.$refs.panelContainer,
    });
    axios
      .get('/api/getMgQuickFireReport')
      .then(
        response => (
          loader.hide(),
          this.links = response.data.links,
          this.summary = response.data.summary,
          this.transactions = response.data.transactions
          // this.dataTable = $('#mg_quick_table').DataTable({
          //   'paging':true,
          //   // 'ordering':false,
          //   // 'info':false,
          // }),
          // setTimeout(() => this.transactions = response.data.transactions, 100)
        )
      );
      
  },
  watch: {
    filter(value) {
      if(value === false) this.username = '';
    },
    // transactions() {
    //   this.dataTable.destroy();
    //   this.$nextTick(() => {
    //     this.dataTable = $('#mg_quick_table').DataTable({
    //       'paging':true,
    //       // 'ordering':false,
    //       // 'info':false,
    //     });
    //   });
    // },
  },
  computed: {
    generateTitle: function () {
      return this.username ? ` of player ${this.username}` : '';
    },
  },
};

</script>

