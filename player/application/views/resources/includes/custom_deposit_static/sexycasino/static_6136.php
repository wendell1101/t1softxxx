 <?php if($currentLang == 'thai') :?>
    <div class="deposit-detail-content guides" style="padding-top:20px">
        <p class="text-red text-center">กรุณาใช้บัญชีที่ท่านสมัครโอนมาเท่านั้น</p>
        <p class="text-yellow text-center">*ระบบจะเติมเครดิตเกมอัตโนมัติ ภายในเวลา 1-5 นาที</p>

        <div class="bank-information text-center">
            <img src="<?= $account_icon_url ?>" >
            <p>ชื่อบัญชี : อัศวิน ระดมสุข</p>
            <p>เลขที่ : 077-280-9057</p>
            <p>ไทยพาณิชย์</p>
            <p class="text-yellow">ขั้นต่ำการฝากเงิน 1 บาท สูงสุด 200,000 บาท</p>
        </div>

        <div class="depo-notice">
            <p class="text-red">***คำเตือน</p>
            <p class="text-red">1. ถ้า User ลูกค้าที่ลงทะเบียนใช้ รนาคารไทยพาณิชย์ SCB ไม่สามารถฝากช่องทางนี้ได้
                (แต่สามารถฝากช่องทางอื่นได้ )</p>
            <p class="text-red">2. การฝากผ่านช่องทางนี้ไม่สามารถรับโปรโมชันหน้าเว็บไซต์แบบอัตโนมัติได้</p>
        </div>
        <p class="text-yellow text-center mt-2">หากเครดิตไม่เข้าภายใน 5 นาที กรุณาติดต่อ Admin</p>
    </div>
<?php else: ?>
    <div class="deposit-detail-content guides" style="padding-top:20px">
        <p class="text-red text-center">Please use your registered bank account only.</p>
        <p class="text-yellow text-center">System will top up your credit automatically within 1-5 minutes</p>

        <div class="bank-information text-center">
            <img src="<?= $account_icon_url ?>" >
            <p>Account name : Assawin Radomsook </p>
            <p>Account number: 077-280-9057</p>
            <p>Siam Commercial Bank (SCB)</p>
            <p class="text-yellow">Minimum Deposit is 1 baht and Maximum is 200,000 baht </p>
        </div>

        <div class="depo-notice">
            <p class="text-red">***Notice</p>
            <p class="text-red">1. If user registered back account with SCB, please use another deposit channel.</p>
            <p class="text-red">2. Deposit with Skydive will cannot get promotion automatically.</p>
        </div>
        <p class="text-yellow text-center mt-2">If credit don't top up your account automatically within 5 minutes please contact our admin.</p>
    </div>
<?php endif; ?>

<style>
    .guides p{
        font-size: 20px;
        color: #fff;
    }
    .guides p.text-red{
        color: #fb0000;
    }
    .guides p.text-yellow{
        color: #F8F500;
    }
    .guides .bank-information  {
        margin: 10px 0;
    }
    .guides .bank-information img  {
        border-radius: 15px;
        margin: 10px 0;
        width: 150px;
    }
    .guides .depo-notice  {
       margin-bottom: 20px;
    }
    @media only screen and (max-width: 768px) {
        .guides {
            padding: 20px;
        }
        .guides p{
            font-size: 14px;
        }
    }
</style>