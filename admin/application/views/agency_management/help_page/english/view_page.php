<style type="text/css">
    .tabs__sec__wrapper {
        margin: 50px 0;
    }
    .tabs__sec__wrapper .tabs__content__wrapper {
        border: 3px #5697D1 solid;
        border-radius: 2.5rem;
        overflow: hidden;
        padding: 20px 0;
    }
    .tabs__sec__wrapper .tabs__content__wrapper .col-md-3 {
        padding-left: 0;
    }
    .tabs__sec__wrapper .tabs__wrapper ul {
        border: 0;
    }
    .tabs__sec__wrapper .tabs__wrapper ul li {
        float: none; 
        margin-bottom: 3px;
    }
    .tabs__sec__wrapper .tabs__wrapper ul li a {
        padding: 15px 15px 15px 35px;
    }
    .tabs__sec__wrapper .tabs__wrapper ul li a:hover {
        border-radius: 0 180px 180px 0;
    }
    .tabs__sec__wrapper .tabs__wrapper ul li.active a {
        border: 0;
        background: #5697D1;
        border-radius: 0 180px 180px 0;
        color: #fff !important;
    }
    .tabs__content__wrapper .tabs__wrapper__right {
        background: #EAEAEA;
        border-radius: 1.5rem;
        padding: 15px;
    }
    .tabs__content__wrapper .tabs__wrapper__right h2 {
        margin: 0;
        padding: 0 0 20px 0;
    }
    .tabs__content__wrapper .tabs__wrapper__right h3 {
        margin: 0;
        color: #5697D1;
        padding: 0 0 15px 0;
    }
    .tabs__content__wrapper .tabs__wrapper__right p {
        margin: 0;
        padding: 0 0 15px 0;
    }

    .nav-tabs li a {
      margin-right: 2px !important;
      line-height: 1.42857143 !important;
      border: 1px solid transparent !important;
      border-radius: 4px 4px 0 0 !important;
    }
</style>
<div>
    <section class="tabs__sec__wrapper">
        <div class="container">
            <div class="row">
                <div class="tabs__content__wrapper">
                    <div class="col-md-3">
                        <div class="tabs__wrapper">
                            <ul class="nav nav-tabs" role="tablist">
                                <!-- <li class="active">
                                    <a href="#about" role="tab" data-toggle="tab">
                                        About
                                    </a>
                                </li> -->
                                <li>
                                    <a href="#view_credit_transaction" role="tab" data-toggle="tab">
                                        Credit Transaction
                                    </a>
                                </li>
                                <li>
                                    <a href="#view_agency_logs" role="tab" data-toggle="tab">
                                        Agency Logs
                                    </a>
                                </li>
                                <li>
                                    <a href="#view_template_list" role="tab" data-toggle="tab">
                                        Template List
                                    </a>
                                </li>
                                <li>
                                    <a href="#view_agency_list" role="tab" data-toggle="tab">
                                        Agency List
                                    </a>
                                </li>
                                <li>
                                    <a href="#view_domain_list" role="tab" data-toggle="tab">
                                        Domain List
                                    </a>
                                </li>
                                <li>
                                    <a href="#view_agency_payment" role="tab" data-toggle="tab">
                                        Agency Payment
                                    </a>
                                </li>
                                <li>
                                    <a href="#view_commission_setting" role="tab" data-toggle="tab">
                                        Commission Terms and Settings
                                    </a>
                                </li>
                                <li>
                                    <a href="#view_formulas" role="tab" data-toggle="tab">
                                        Formula(s)
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-9">

                        <div class="tabs__wrapper__right">

                            <div class="tab-content">
                              
                                <div class="tab-pane fade active in" id="about">
                                    <h3>Welcome to agency help page.</h3>
                                </div>

                                <div class="tab-pane fade" id="view_credit_transaction">
                                    <h3>Credit Transaction list</h3>
                                    <p>The credit transaction shows the balance movement of agents and players who have an agent.</p>

                                    <h3>How to set the amount of credit for an agent?</h3>
                                    <p>Go to agent information. Under basic information click the “Adjust Credit” button select in the “Transaction type” the “Manual add credit” and fill out the input field under “Amount”.</p>
                                    <p>Note: The inputted credit cannot be exceeded to the credit limit.</p>

                                    <h3>What is the credit limit?</h3>
                                    <p>Setting maximum amount to be given by the agent to its binded player.</p>
                                </div>

                                <div class="tab-pane fade" id="view_agency_logs">
                                    <h3>What are agency logs for?</h3>
                                    <p>To track agent’s performance/action in its account. </p>
                                </div>

                                <div class="tab-pane fade" id="tl">
                                    <h3>What is the purpose of a template for agency?</h3>
                                    <p>Helps to create an agent with an arranged or set fees, permission, commission settings, default VIP level, and settlement settings.</p>
                                  
                                    <h3>What are the Permission settings?</h3>
                                    <p>Settings that can be set for an agent to access agency after an account is created.</p>

                                    <h3>What are the Commision settings?</h3>
                                    <p>Commission is based on the pattern name or the computation of the commission assigned to an agent.</p>

                                    <h3>What is the settlement setting for?</h3>
                                    <p> Period basis of the system for computing the assigned commission of the agent.
                                    Daily - Agents’ Commission will be computed on a daily basis.
                                    Weekly - Agents’ Commission will be computed on a weekly basis.
                                    Monthly - Agents’ Commission will be computed on a monthly basis.
                                    Manual - Agents’ settlement will be processed by an admin user.</p>
                                </div>

                                <div class="tab-pane fade" id="view_template_list">
                                    <h3>What is the purpose of a template for agency?</h3>
                                    <p>Helps to create an agent with an arranged or set fees, permission, commission settings, default VIP level, and settlement settings.</p>
                                  
                                    <h3>What are the Permission settings?</h3>
                                    <p>Settings that can be set for an agent to access agency after an account is created.</p>

                                    <h3>What are the Commision settings?</h3>
                                    <p>Commission is based on the pattern name or the computation of the commission assigned to an agent.</p>

                                    <h3>What is the settlement setting for?</h3>
                                    <p> Period basis of the system for computing the assigned commission of the agent.
                                    Daily - Agents’ Commission will be computed on a daily basis.
                                    Weekly - Agents’ Commission will be computed on a weekly basis.
                                    Monthly - Agents’ Commission will be computed on a monthly basis.
                                    Manual - Agents’ settlement will be processed by an admin user.</p>
                                </div>

                                <div class="tab-pane fade" id="view_agency_list">
                                  <h3>What is the difference between suspended and frozen agent accounts?</h3>
                                  <p>Suspended refers to an agent that is allowed to log in to the agency page but not allowed to do any transaction. Frozen refers to an agent that is not allowed to log in to the agency page.</p>
                                
                                  <h3>How to edit an agency's player default VIP group?</h3>
                                  <p>Path Link: Agency > Agent List > Select the agent username > Open Basic Information > Click “Edit”.</p>
                                  <p>After clicking the edit button, scroll down to the bottom of the page, and you will see the Default Player VIP Level section. > Click the dropdown field > Select the VIP group and level.</p>

                                  <h3>Are the system-generated tracking codes editable?</h3>
                                  <p>You can edit them by going to Agency System > Setting > Tracking link > Unlock tracking code.</p>

                                  <h3>How do I add a sub-agent or refer to a potential sub-agent? And where can I see them?</h3>
                                  <p>You can edit them by going to Agency System > Setting > Tracking link > Unlock tracking code.
                                  <br>- Agency System > Setting > Tracking link > Sub-agent link
                                  <br>- Agency System > Agent Information > Agent Hierarchy</p>
                                </div>

                                <div class="tab-pane fade" id="view_domain_list">
                                  <h3>Domain</h3>
                                  <p>Where agents, sub-agents, and players can access the agent page.</p>

                                  <h3>How to add a new domain?</h3>
                                  <p>1. Fill out the following fields located on the right side of the page [Domain, Domain Visibility, Notes]
                                    <br>2. Click the Add button
                                  </p>
                                </div>
                                
                                <div class="tab-pane fade" id="view_agency_payment">
                                  <h3>Agency Payment</h3>
                                  <p>This is where an SBE user approved or declined withdrawal transactions of the agent.</p>
                                </div>

                                <div class="tab-pane fade" id="view_commission_setting">
                                  <h3>What is the "Current" meaning in the settlement report?</h3>
                                  <p>Path Link: Agency bo > Report > Agent Win Lose Comm Settlement<br>
                                      Depends on the Settlement Period which agent be set.<br>
                                      If set to weekly, then "Current" only will show current settlement week.<br>
                                      If set to monthly, then "Current" only will show the current settlement month.</p>
                                  <h3>What is the basis of the rolling commission percentage?</h3>
                                  <p>The percentage will based depending the selected dropdown in rolling commission basis;</p>
                                  <p>
                                    <b>Total Bets:</b> Will consider all bets in agents’ commission computation<br>
                                    <b>Lost Bets:</b> Will consider only the lost bets in agents’ commission computation<br>
                                    <b>Winning Bets:</b> Will consider only the winning bets in agents’ commission computation<br>
                                    <b>Total Bets Except Tie Bets:</b> Will consider total bet less tie bets in agents’ commission computation<br>
                                  </p>
                                  <h3>Fees</h3>
                                  <p>Additional charges</p>
                                  <ul>
                                    <li>Admin Fee - charges by the company as maintenance and service fee. (suggested: 10-15%)
                                    </li>
                                    <li>Bonus Fee - bonus cost shared by company and agents (suggested: 100%)
                                    </li>
                                    <li>Deposit and Withdraw Fee - transaction cost shared by company and agents. (suggested: 1-3%)</li>
                                    <li>Cashback Fee -charges for the cashback received by the player. (suggested: 100%)</li>
                                  </ul>
                                </div>
                                <div class="tab-pane fade" id="view_formulas">
                                  <h3>Formula 1: (use Transaction Fee)</h3>
                                  <p>[Game Revenue*(1-Platform Fee(%))*(1-Admin Fee(%))-(Transaction*Transaction Fee(%)+Bonus*Bonus Fee(%)+Cashback*Cashback Fee(%))]*Rev Share(%)+Deposit Commission(if have set)=Agent Commission</p>
                                  <p>Calculation：[11,725.27*(1-0%)*(1-14%)-(16.48+0+351.55)]*70%+0=6800.99154</p>
                                  <p>Agency > Agent List (Find the Parent Agent to check Fee settings, such as JH1012 is agent level 2, JH101 is agent level 1, KGJH001 is agent level 0(means JH101's Parent Agent)
                                  </p>
                                  <p>Agency > Agent List (Find agent: JH1012 to check Rev Share(%) and Deposit Commission)</p>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>