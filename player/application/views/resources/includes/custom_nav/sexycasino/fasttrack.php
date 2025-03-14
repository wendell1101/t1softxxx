<div class="right fasttrack__btn">
     <div class="inbox-container" style="position: relative">
          <a href="javascript:void(0)" class="btn">
               <!-- <img src="https://www.sexycasino.com/includes/images/inbox.png?v2" alt="" style="width: 20px; position: relative;" /> -->
               <img src="<?=$this->utils->getSystemUrl('m', '/includes/images/inbox.png?v2');?>" alt="" style="width: 28px; position: relative;" />
               <span><?=lang('announcement');?></span>
          </a>
          <span class="badge" id="ftInbox" style="display: none">0</span>
     </div>
</div>

<script type="text/javascript">
     // Sidenav
     $(".fasttrack__btn").click(function () {
          if (window.FasttrackCrm) {
               window.FasttrackCrm.toggleInbox();
          }
     });
</script>
<style>
     .fasttrack__btn{
          width: 130px;
          height: 45px;
          margin-left: 3px;
          font-size: 0.5rem;
     }
</style>
