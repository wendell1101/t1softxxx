<?php include __DIR__ . '../../../includes/big_wallet_details.php'; ?>
<?php include __DIR__ . '../../../includes/popup_promorules_info.php'; ?>

<style type="text/css">
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .table-header {
        background-color: #d9b38c;
    }

    .table-summary {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .highlight {
        background-color: #cce5ff;
    }

    .text-right {
        text-align: right;
    }
</style>

<div class="container mt-4">
    <form action="#" method="POST" id="form-filter">
        <!-- Date, Username, and Main Info Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="dateFrom"><strong>Date From:</strong></label>
                    <input type="datetime-local" class="form-control" id="from_date" name="from_date" required>
                </div>
                <div class="form-group">
                    <label for="dateTo"><strong>Date To:</strong></label>
                    <input type="datetime-local" class="form-control" id="to_date" name="to_date" required>
                </div>
                <div class="form-group">
                    <label for="username"><strong>Player Username:</strong></label>
                    <input type="text" class="form-control" id="player_name" name="player_name" required>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Submit</button>
            </div>
            <div class="col-md-8">
                <table class="table table-bordered">
                    <tr>
                        <th>Fund in - Fund Out</th> <!-- total balance difference manual adjustment included -->
                        <td class="text-right" id="total_fund_in_fund_out">-</td>
                    </tr>
                    <tr>
                        <th>Fund Transfer to Sub Wallet - Fund Transfer to Main Wallet</th>
                        <td class="text-right" id="total_fund_transfer_to_subwallet_to_mainwallet">-</td>
                    </tr>
                    <tr>
                        <th>Difference</th>
                        <td class="text-right" id="total-balance-difference">-</td>
                    </tr>
                    <tr>
                        <th>Waiting For Withdraw</th>
                        <td class="text-right" id="pending_withdrawal_amount">-</td>
                    </tr>
                    <tr>
                        <th>Wallet</th>  <!-- current balance -->
                        <td class="text-right" id="current_player_balance">-</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Transactions Report Section -->
        <h5 class="text-left table-header p-10">Transactions Report</h5>
        <table class="table table-bordered">
            <thead>
                <tr class="table-summary">
                    <th>Fund In</th>
                    <th>Wallet</th>
                    <th>Fund Transfer to Main Wallet</th>
                    <th>Fund Transfer to Sub Wallet</th>
                    <th>Fund Transfer to Sub Wallet - Fund Transfer to Main Wallet</th>
                    <th>Game Payout</th>
                    <th>Expected Wallet Balance</th>
                    <th>Actual Wallet Balance</th>
                    <th>Balance Difference</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody id="transactions-table-body">
                <!-- Dynamic rows will be added here -->
            </tbody>
            <tfoot>
                <tr class="table-summary" id="balance-summary">
                    <td id="total-fund-in"></td>
                    <td id="total-wallet"></td>
                    <td id="total-main-wallet"></td>
                    <td id="total-sub-wallet"></td>
                    <td id="total-subwallet-mainwallet-difference"></td>
                    <td id="total-game-payout"></td>
                    <td id="total-expected-balance"></td>
                    <td id="total-actual-balance"></td>
                    <td id="total_actual_wallet_minus_expected_wallet_amount"></td>
                    <td id="total-note"></td>
                </tr>
            </tfoot>
        </table>


        <!-- Manual Entries Section -->
        <h5 class="text-left table-header p-10">Manual Adjustments</h5>

        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <thead class="p-10">Fund In</thead>
                    <tbody id="manual-adjustment">
                        <tr>
                            <th>Deposit</th>
                            <td class="text-right" id="total-manual-deposit-balance">-</td>
                        </tr>
                        <tr>
                            <th>Add Bonus</th>
                            <td class="text-right" id="total-manual-add-bonus">-</td>
                        </tr>
                        <tr>
                            <th>Cashback</th>
                            <td class="text-right" id="total-manual-add-cashback">-</td>
                        </tr>
                        <tr>
                            <th>Referral Bonus</th>
                            <td class="text-right" id="total-manual-add-referral-bonus">-</td>
                        </tr>
                        <tr>
                            <th>VIP Bonus</th>
                            <td class="text-right" id="total-manual-add-vip-bonus">-</td>
                        </tr>
                        <tr>
                            <th>Manual Add Balance</th>
                            <td class="text-right" id="total-manual-add-balance"></td>
                        </tr>
                        <tr class="table-summary">
                            <th>Total Fund In</th>
                            <td class="text-right" id="total-manual-fund-in">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <table class="table table-bordered">
                    <thead class="p-10">Fund Out</thead>
                    <tbody id="manual-adjustment">
                        <tr>
                            <th>Withdrawal</th>
                            <td class="text-right" id="total-manual-withdraw-balance">-</td>
                        </tr>
                        <tr>
                            <th>Subtract bonus</th>
                            <td class="text-right" id="total-manual-deduct-bonus">-</td>
                        </tr>
                        <tr>
                            <th>Manual subtract balance</th>
                            <td class="text-right" id="total-manual-deduct-balance">-</td>
                        </tr>
                        <tr>
                            <th>Withdrawal fee from player</th>
                            <td class="text-right" id="total-manual-withdraw-fee-from-player">-</td>
                        </tr>
                        <tr>
                            <th>Manually subtract withdrawal fee</th>
                            <td class="text-right" id="total-manual-subtract-withdrawal-fee">-</td>
                        </tr>
                        <tr>
                            <th>Total Fund Out</th>
                            <td class="text-right" id="total-manual-fund-out">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gamelogs Report Section -->
        <h5 class="text-left table-header p-10">Game History Report</h5>
        <table class="table table-bordered">
            <thead>
                <tr class="table-summary">
                    <th>System Name</th>
                    <th>Bet Amount</th>
                    <th>Win Amount</th>
                    <th>Game Payout</th>
                </tr>
            </thead>
            <tbody id="gamelogs-table-body">
                <!-- Dynamic rows will be added here -->
            </tbody>
            <tfoot>
                <tr class="table-summary" id="balance-summary">
                    <td id="game_logs_system_name">-</td>
                    <td id="game_logs_total_bet_amount">-</td>
                    <td id="game_logs_total_win_amount">-</td>
                    <td id="game_logs_total_result_amount">-</td>
                </tr>
            </tfoot>

        </table>
    </form>
</div>


<script type="text/javascript">
    $(document).ready(function() {
        // set default value
        const now = new Date();
        
        // Set the time to 00:00:00
        now.setHours(0, 0, 0, 0);
        
        // Format the date to 'YYYY-MM-DDTHH:MM'
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0'); // Months are 0-based
        const day = String(now.getDate()).padStart(2, '0');
        const formattedFromDate = `${year}-${month}-${day}T00:00`;
        const formattedEndDate = `${year}-${month}-${day}T23:59`;

        // Set the default value to the input field
        document.getElementById('from_date').value = formattedFromDate;
        document.getElementById('to_date').value = formattedEndDate;

        $('#form-filter').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            const fromDate = document.getElementById('from_date').value;
    
            const toDate = document.getElementById('to_date').value;

            // Collect form data
            var formData = $(this).serializeArray();

            // Add the date values to the serialized form data
            formData.push({ name: 'fromDate', value: fromDate });
            formData.push({ name: 'toDate', value: toDate });

            // Convert formData to an object if needed
            var data = {};
            formData.forEach(item => {
                data[item.name] = item.value;
            });

            console.log(data);
            // Send AJAX request
            $.ajax({
                url: base_url + "api/balanceCheckReport",
                type: "POST",
                data: JSON.stringify(data),
                dataType: 'json',
                success: function(response) {
                    console.log("here", response);
                    const total_manual_deposit_balance = response.extra.manual_adjustments.total_manual_deposit_balance || 0;
                    const total_manual_add_balance = response.extra.manual_adjustments.total_manual_add_balance || 0;
                    const total_manual_add_bonus = response.extra.manual_adjustments.total_manual_add_bonus || 0;
                    const total_manual_add_cashback = response.extra.manual_adjustments.total_manual_add_cashback || 0;
                    const total_manual_add_referral_bonus = response.extra.manual_adjustments.total_manual_add_referral_bonus || 0;
                    const total_manual_add_vip_bonus = response.extra.manual_adjustments.total_manual_add_vip_bonus || 0;
                    const total_manual_fund_in = response.extra.manual_adjustments.total_manual_fund_in || 0;

                    const total_manual_withdraw_balance = response.extra.manual_adjustments.total_manual_withdraw_balance || 0;
                    const total_manual_deduct_balance = response.extra.manual_adjustments.total_manual_deduct_balance || 0;
                    const total_manual_deduct_bonus = response.extra.manual_adjustments.total_manual_deduct_bonus || 0;

                    const total_manual_withdraw_fee_from_player = response.extra.manual_adjustments.total_manual_withdraw_fee_from_player || 0;
                    const total_manual_subtract_withdrawal_fee = response.extra.manual_adjustments.total_manual_subtract_withdrawal_fee || 0;
                 
                    const total_manual_fund_out = response.extra.manual_adjustments.total_manual_fund_out || 0;

                    const total_fund_in_fund_out = response.extra.summary.total_fund_in_fund_out || 0;
                    const total_fund_transfer_to_subwallet_to_mainwallet = response.extra.summary.total_fund_transfer_to_subwallet_to_mainwallet || 0;
                    const current_player_balance = response.extra.summary.current_player_balance || 0;
                    const pending_withdrawal_amount = response.extra.summary.pending_withdrawal_amount || 0;


                    const game_logs_total_bet_amount = response.extra.gamelogs.game_logs_total_bet_amount || 0;
                    const game_logs_total_win_amount = response.extra.gamelogs.game_logs_total_win_amount || 0;
                    const game_logs_total_result_amount = response.extra.gamelogs.game_logs_total_result_amount || 0;
                    
                    // Clear previous table data
                    $('#transactions-table-body').empty();
                    $('#gamelogs-table-body').empty();

                    let transactionDate = null;
                    let total_fund_in = response.extra.automatic_adjustments.total_fund_in || 0;
                    let total_fund_transfer_to_mainwallet =  response.extra.automatic_adjustments.total_fund_transfer_to_mainwallet || 0;
                    let total_fund_transfer_to_subwallet = response.extra.automatic_adjustments.total_fund_transfer_to_subwallet || 0;
                    let total_game_payout = response.extra.automatic_adjustments.total_game_payout || 0;
                    // let total_expected_wallet_balance = response.extra.automatic_adjustments.total_expected_wallet_balance || 0;
                    // let total_actual_wallet_balance = response.extra.automatic_adjustments.total_actual_wallet_balance || 0;
                    let total_balance_difference = response.extra.automatic_adjustments.total_balance_difference || 0;
                    let total_expected_wallet_balance = response.extra.automatic_adjustments.total_expected_wallet_balance || 0;
                    let total_actual_wallet_balance = response.extra.automatic_adjustments.total_actual_wallet_balance || 0;
                    let total_actual_wallet_minus_expected_wallet_amount = response.extra.automatic_adjustments.total_actual_wallet_minus_expected_wallet_amount || 0;
        
                    response.transactions.forEach(function(item) {  
                      //transaction_types refer to transcations.php
                        $('#transactions-table-body').append(`
                            <tr>
                                <td>${parseFloat(item.fund_in)}</td>
                                <td>${item.subwallet || 'Main Wallet'}</td>
                                <td>${parseFloat(item.mainwallet_amount)}</td>
                                <td>${parseFloat(item.subwallet_amount)}</td>
                                <td>${parseFloat(item.balance_difference || 0)}</td>
                                <td>${parseFloat(item.game_payout)}</td>
                                <td>${parseFloat(item.expected_wallet_balance)}</td>
                                <td>${parseFloat(item.subwallet_balance)}</td>
                                <td>${parseFloat(item.actual_wallet_minus_expected_wallet_amount || 0)}</td>
                                <td>${item.note}</td>
                            </tr>
                        `);
                    });

                    
                    response.gamelogs.forEach(function(item) {
                        console.log('game_logs', item);
                      // Create a new row
                      $('#gamelogs-table-body').append(`
                          <tr>
                              <td>${item.system_name}</td>
                              <td>${parseFloat(item.bet_amount)}</td>
                              <td>${parseFloat(item.win_amount)}</td>
                              <td>${parseFloat(item.result_amount)}</td>
                          </tr>
                      `);
                  });

                    // Update the footer with total values
                    $('#total-fund-in').text(`Total Fund In: ${total_fund_in}`);
                    $('#total-main-wallet').text(`Total Fund Transfer to Main Wallet: ${total_fund_transfer_to_mainwallet}`);
                    $('#total-sub-wallet').text(`Total Fund Transfer to Sub Wallet: ${total_fund_transfer_to_subwallet}`);
                    $('#total-subwallet-mainwallet-difference').text(`Subwallet-Mainwallet Difference: ${total_balance_difference}`);
                    $('#total-game-payout').text(`Total Game Payout: ${total_game_payout}`);
                    $('#total-expected-balance').text(`Total Expected Wallet Balance: ${total_expected_wallet_balance}`);
                    $('#total-actual-balance').text(`Total Actual Wallet Balance: ${total_actual_wallet_balance}`);
                    $('#total_actual_wallet_minus_expected_wallet_amount').text(`Total balance difference: ${total_actual_wallet_minus_expected_wallet_amount}`);


                    $('#total-manual-deposit-balance').text(`${total_manual_deposit_balance}`);
                    $('#total-manual-add-balance').text(`${total_manual_add_balance}`);
                    $('#total-manual-add-bonus').text(`${total_manual_add_bonus}`);
                    $('#total-manual-add-cashback').text(`${total_manual_add_cashback}`);
                    $('#total-manual-add-referral-bonus').text(`${total_manual_add_referral_bonus}`);
                    $('#total-manual-add-vip-bonus').text(`${total_manual_add_vip_bonus}`);
                    $('#total-manual-fund-in').text(`${total_manual_fund_in}`);

                    $('#total-manual-withdraw-balance').text(`${total_manual_withdraw_balance}`);
                    $('#total-manual-deduct-balance').text(`${total_manual_deduct_balance}`);
                    $('#total-manual-deduct-bonus').text(`${total_manual_deduct_bonus}`);
                    $('#total-manual-withdraw-fee-from-player').text(`${total_manual_withdraw_fee_from_player}`);
                    $('#total-manual-subtract-withdrawal-fee').text(`${total_manual_subtract_withdrawal_fee}`);
                    $('#total-manual-fund-out').text(`${total_manual_fund_out}`);


                    $('#total_fund_in_fund_out').text(`${total_fund_in_fund_out}`);
                    $('#total_fund_transfer_to_subwallet_to_mainwallet').text(`${total_fund_transfer_to_subwallet_to_mainwallet}`);
                    $('#total-balance-difference').text(`${total_balance_difference}`);
                    $('#current_player_balance').text(`${current_player_balance}`);
                    $('#pending_withdrawal_amount').text(`${pending_withdrawal_amount}`);



                    $('#total-fund-in').text(`Total Fund In: ${total_fund_in}`);
                    $('#total-main-wallet').text(`Total Fund Transfer to Main Wallet: ${total_fund_transfer_to_mainwallet}`);
                    $('#total-sub-wallet').text(`Total Fund Transfer to Sub Wallet: ${total_fund_transfer_to_subwallet}`);
                    $('#total-game-payout').text(`Total Game Payout: ${total_game_payout}`);
                    $('#total-expected-balance').text(`Total Expected Wallet Balance: ${total_expected_wallet_balance}`);
                    $('#total-actual-balance').text(`Actual Wallet Balance: ${total_actual_wallet_balance}`);
                    $('#game_logs_total_result_amount').text(`Total Result Amount: ${game_logs_total_result_amount}`);
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                    console.error("Status:", status);
                    console.error("Response:", xhr.responseText);
                    // Display an error message or handle the error
                }
            });
        });
    });
</script>