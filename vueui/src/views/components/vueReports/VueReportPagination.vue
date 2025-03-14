<style>
    ul.pagination {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex; 
    }
    ul.pagination li {
        list-style: none;
    }
    ul.pagination li button {
        background: none;
        border: 1px solid #dee2e6;
        padding: 10px 15px;
        margin-left: -1px;
        outline: none;
    }
    ul.pagination li:first-child button {
        border-radius: 3px 0 0 3px;
    }
    ul.pagination li:last-child button {
        border-radius: 0 3px 3px 0;
    }
    ul.pagination li.active button {
        background-color: rgb(25, 157, 197);
        color: #ffffff;
        border-color: rgb(25, 157, 197);
    }
</style>
<template>
  <ul class="pagination pull-right">
    <li>
      <button
        type="button"
        @click="onClickFirstPage"
        :disabled="isInFirstPage"
        aria-label="Go to first page"
      >
        First
      </button>
    </li>

    <li>
      <button
        type="button"
        @click="onClickPreviousPage"
        :disabled="isInFirstPage"
        aria-label="Go to previous page"
      >
        Previous
      </button>
    </li>

    <li v-for="page in pages"
      :key="page.name"
      :class="{ active: isPageActive(page.name) }"
    >
      <button
        type="button"
        @click="onClickPage(page.name)"
        :disabled="page.isDisabled"
        :aria-label="`Go to page number ${page.name}`"
      >
        {{ page.name }}
      </button>
    </li>

    <li>
      <button
        type="button"
        @click="onClickNextPage"
        :disabled="isInLastPage"
        aria-label="Go to next page"
      >
        Next
      </button>
    </li>

    <li>
      <button
        type="button"
        @click="onClickLastPage"
        :disabled="isInLastPage"
        aria-label="Go to last page"
      >
        Last
      </button>
    </li>
  </ul>
</template>


<script>
export default {
  props: {
    maxVisibleButtons: {
      type: Number,
      required: false,
      // default: 3,
    },
    totalPages: {
      type: Number,
      required: true,
    },
    total: {
      type: Number,
      required: true,
    },
    currentPage: {
      type: Number,
      required: true,
    },
  },
  computed: {
    startPage() {
      // console.log(this.totalPages);
      // When on the first page
      if (this.currentPage === 1) {
        return 1;
      }
      // When on the last page
      if (this.currentPage === this.totalPages) {
        return this.totalPages - this.maxVisibleButtons + 1;
      }
      // When in between
      return this.currentPage - 1;
    },
    endPage() {
      
      return Math.min(this.startPage + this.maxVisibleButtons - 1, this.totalPages);
      
    },
    pages() {
      const range = [];

      for (let i = this.startPage;
        i <= Math.min(this.startPage + this.maxVisibleButtons - 1, this.totalPages);
        i+= 1 ) {
        range.push({
          name: i,
          isDisabled: i === this.currentPage,
        });
      }
      return range;
    },
    isInFirstPage() {
      return this.currentPage === 1;
    },
    isInLastPage() {
      return this.currentPage === this.totalPages;
    },
  },
  methods: {
    onClickFirstPage() {
      this.$emit('pagechanged', 1);
    },
    onClickPreviousPage() {
      this.$emit('pagechanged', this.currentPage - 1);
    },
    onClickPage(page) {
      this.$emit('pagechanged', page);
    },
    onClickNextPage() {
      this.$emit('pagechanged', this.currentPage + 1);
    },
    onClickLastPage() {
      this.$emit('pagechanged', this.totalPages);
    },
    isPageActive(page) {
      return this.currentPage === page;
    },
  },
};
</script>

