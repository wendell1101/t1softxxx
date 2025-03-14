<!-- review_haba_api_results Modal Start -->

<div class="modal fade" id="review_haba_api_resultsModal" tabindex="-1" role="dialog" aria-labelledby="review_haba_api_resultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="review_haba_api_resultsModalLabel"><?=lang('Review Habanero API Results')?></h4>
            </div>
            <div class="modal-body review_haba_api_resultsModalBody">

                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="h3 review_haba_api_results_title">
                            </div>

                            <form id="search-rharm-form">
                                <input type="hidden" name="playerpromo_id" value="">
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered table-striped table-hover" id="review_haba_api_results_list" >
                                <thead>
                                    <tr>
                                        <th><?= lang('ID'); ?></th>
                                        <th><?= lang('Player Username'); ?></th>
                                        <th><?= lang('Game Name'); ?></th>
                                        <th><?= lang('Result'); ?></th>
                                        <th><?= lang('Message'); ?></th>
                                        <th><?= lang('Request'); ?></th>
                                        <th><?= lang('Response'); ?></th>
                                        <th><?= lang('Created At'); ?></th>
                                        <th><?= lang('Updated At'); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=lang('Close')?></button>
            </div>
        </div>
    </div>
</div>
<!-- review_haba_api_results Modal End -->