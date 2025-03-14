<?php
trait playercenter_cmspopup_module
{
    /**
     * view popup manager page
     *
     * @return  void
     */

    public function viewPopupManager($offset = 0)
    {
        if (!$this->permissions->checkPermissions('popupcms')) {
            $this->error_access();
        } else {
            $this->loadTemplate(lang('Pop-up Manager'), '', '', 'cms');

            $config['base_url'] = "/cms_management/viewPopupManager/";

            $condition = [];
            if ($categoryId = $this->input->get('categoryId')) {
                $condition['categoryId'] = $categoryId;
            }
			$condition['deleted_on'] = null;

            $config['total_rows'] = count($this->cms_model->getAllNewsPopups(null, null, null, $condition, false));
            $config['per_page'] = 10;
            $config['num_links'] = '5';

            $config['first_tag_open'] = '<li>';
            $config['last_tag_open'] = '<li>';
            $config['next_tag_open'] = '<li>';
            $config['prev_tag_open'] = '<li>';
            $config['num_tag_open'] = '<li>';

            $config['first_tag_close'] = '</li>';
            $config['last_tag_close'] = '</li>';
            $config['next_tag_close'] = '</li>';
            $config['prev_tag_close'] = '</li>';
            $config['num_tag_close'] = '</li>';

            $config['cur_tag_open'] = "<li><span><b>";
            $config['cur_tag_close'] = "</b></span></li>";

            $this->pagination->initialize($config);
            $this->load->library(array('cmsbanner_library'));

            $data['condition'] = $condition;
            $popup = $this->cms_model->getAllNewsPopups($config['per_page'], $offset, 'created_at desc', $condition, false);
            foreach ($popup as & $row) {
                if ($row['is_default_banner'] != 1) {
                    if (!empty($row['banner_url']) && file_exists($this->cmsbanner_library->getUploadPath($row['banner_url']))) {
                        $row['banner_url'] = $this->utils->getSystemUrl('player') . $this->cmsbanner_library->getPublicPath($row['banner_url']);
                    }
                }
            }

            $data['popup'] = $popup;
			$condition['set_visible'] = 1;
            $popup_visible = $this->cms_model->getAllNewsPopups(null, null, 'created_at desc', $condition, false);

            foreach ($popup_visible as & $popup) {
                if ($popup['is_default_banner'] != 1) {
                    if (!empty($popup['banner_url']) && file_exists($this->cmsbanner_library->getUploadPath($popup['banner_url']))) {
                        $popup['banner_url'] = $this->utils->getSystemUrl('player') . $this->cmsbanner_library->getPublicPath($popup['banner_url']);
                    }
                }
            }
			$data['popup_visible'] = $popup_visible;
            $data['newsCategoryList'] = $this->cms_model->getAllNewsCategory(null, null, null);

            $this->template->write_view('main_content', 'cms_management/news/popup/view_popup', $data);
            $this->template->render();
        }
    }

    /**
     * add pop-up page
     *
     * @return  void
     */
    public function addPopup()
    {
        if (!$this->permissions->checkPermissions('popupcms')) {
            $this->error_access();
        } else {
            $this->loadTemplate('CMS Management', '', '', 'cms');
            $this->template->add_css('resources/css/cms_management/cms_popup.css');
			$data['form_title'] = lang('Add Pop-up');
			$data['submit_text'] = lang('lang.add');
            $data['form_action'] = 'cms_management/verifyAddNewsPopup';
            $data['newsCategoryList'] = $this->cms_model->getAllNewsCategory(null, null, null);

            $this->template->write_view('main_content', 'cms_management/news/popup/edit_popup', $data);
            $this->template->render();
        }
    }

    /**
     * verify add news or announcements page
     *
     * @return  void
     */
    public function verifyAddNewsPopup()
    {
        if (!$this->permissions->checkPermissions('popupcms')) {
            $this->error_access();
        } else {
            // $this->form_validation->set_rules('title', 'title', 'trim|xss_clean|required');
            $this->form_validation->set_rules('categoryId', 'Category', 'trim|xss_clean|required');

            if ($this->form_validation->run() == false) {
                $this->addPopup();
            } else {
				$data = $this->inputValueMapping();

                $this->cms_model->addNewspopup($data);

                $this->saveAction('cms_management', 'Add Pop-Up', "User " . $this->authentication->getUsername() . " has successfully added pop-up'" . $data['title'] . "'");

                $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.cms09'));
                redirect(BASEURL . 'cms_management/viewPopupManager', 'refresh');
            }
        }
    }

    /**
     * edit news or announcements page
     *
     * @return  void
     */
    public function editPopup($popup_id)
    {
        $this->loadTemplate('CMS Management', '', '', 'cms');
        $this->load->library(array('cmsbanner_library'));

        $this->template->add_css('resources/css/cms_management/cms_popup.css');

        $popup = $this->cms_model->getNewsPopup($popup_id);
        $popup['content'] = $this->cms_model->decodePromoDetailItem($popup['content']);

        if (empty($popup)) {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Popup not exist.'));
            redirect(BASEURL . 'cms_management/viewPopupManager', 'refresh');
        }
        if ($popup['is_default_banner'] != 1) {
            if (!empty($popup['banner_url']) && file_exists($this->cmsbanner_library->getUploadPath($popup['banner_url']))) {
                $popup['banner_url'] = $this->cmsbanner_library->getPublicPath($popup['banner_url']);
            }
        }
		$data['form_title'] = lang('Edit Pop-up');
		$data['submit_text'] = lang('lang.save');
        $data['popup'] =$popup;
        $data['popup_id'] = $popup_id;
        $data['form_action'] = 'cms_management/verifyEditPopup/'.$popup_id;
        $data['newsCategoryList'] = $this->cms_model->getAllNewsCategory(null, null, null);

        $this->template->write_view('main_content', 'cms_management/news/popup/edit_popup', $data);
        $this->template->render();
    }

    /**
     * verify edit news or announcements page
     *
     * @return  void
     */
    public function verifyEditPopup($popup_id)
    {
        $this->form_validation->set_rules('categoryId', 'Category', 'trim|xss_clean|required');

        if ($this->form_validation->run() == false) {
            $this->editPopup($popup_id);
        } else {
			$data = $this->inputValueMapping();

            $this->cms_model->editPopup($data, $popup_id);

            $this->saveAction('cms_management', 'Edit News Pop-up', "User " . $this->authentication->getUsername() . " has successfully edited pop-up '" . $data['title']);

            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.cms10'));
            redirect(BASEURL . 'cms_management/viewPopupManager', 'refresh');
        }
    }

    /**
     * delete popup
     *
     * @return  void
     */
    public function deletePopup($popup_id, $title)
    {
        $popup = $this->cms_model->getNewsPopup($popup_id, true);
        if ($popup) {
			if($popup['set_visible'] == 1){
				$this->cms_model->refreshPopupVisible();
			}
            $this->cms_model->deletePopup($popup_id);
            $this->saveAction('cms_management', 'Delete Pop-up', "User " . $this->authentication->getUsername() . " has successfully delete pop-up '" . $popup['title'] . "'");
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.cms27') . ': ' . base64_decode($title));
        } else {
            $this->saveAction('cms_management', 'Delete Pop-up', "User " . $this->authentication->getUsername() . " try delete pop-up '" . $popup['title'] . "'");
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('con.cms27') . ': ' . base64_decode($title));
        }
        redirect(BASEURL . 'cms_management/viewPopupManager', 'refresh');
    }

	public function revertDeletePopup($popup_id)
    {
        $popup = $this->cms_model->getNewsPopup($popup_id, true);
        if ($popup) {
			if($popup['set_visible'] == 1){
				$this->cms_model->refreshPopupVisible();
			}
            $this->cms_model->revertDeletePopup($popup_id);
            $this->saveAction('cms_management', 'Revert Delete Pop-up', "User " . $this->authentication->getUsername() . " has successfully revert delete pop-up '" . $popup['title'] . "'");
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.cms27') . ': ' . $popup['title']);
        } else {
            $this->saveAction('cms_management', 'Revert Delete Pop-up', "User " . $this->authentication->getUsername() . " try revert delete pop-up '" . $popup['title'] . "'");
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('con.cms27') . ': ' . $popup['title']);
        }
        redirect(BASEURL . 'cms_management/viewPopupManager', 'refresh');
    }

    public function setPopupToVisible($popup_id)
    {
        $popup = $this->cms_model->getNewsPopup($popup_id);
        if ($popup) {
			$this->cms_model->refreshPopupVisible();
			if($popup['set_visible'] != 1) {
				$this->cms_model->setPopupToVisible($popup_id);
			}
            $this->saveAction('cms_management', 'Setup Pop-up', "User " . $this->authentication->getUsername() . " has successfully setup pop-up '" . $popup['title'] . "'");
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.cms28') . ': ' .$popup['title']);
            redirect(BASEURL . 'cms_management/viewPopupManager', 'refresh');
        } else {
            $this->saveAction('cms_management', 'Setup Pop-up', "User " . $this->authentication->getUsername() . " try setup pop-up '" . $popup['title'] . "'");
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('con.cms28') . ': ' . $popup['title']);
        }
    }

    public function inputValueMapping()
    {
        $title = $this->input->post('title');
        $content = $this->input->post('summernoteDetails');
        $categoryId = $this->input->post('categoryId');
        $isDateRange = $this->input->post('is_daterange');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $display_in = $this->input->post('displayIn-op');
        $display_freq = $this->input->post('displayFreq-op');
        $redirect_to = $this->input->post('redirectTo');
        $redirect_btn_name = $this->input->post('redirectBtnName');
        $redirect_type = $this->input->post('button_link');
        $is_default_banner_flag = $this->input->post('is_default_banner_flag');
        $set_default_banner = $this->input->post('set_default_banner');
        $banner_url = $this->input->post('banner_url');
		$uploadBannerUrl = $this->input->post('upload_banner_url');

        $pathinfo = pathinfo($banner_url,PATHINFO_BASENAME);
        $pathinfo2 = pathinfo($uploadBannerUrl,PATHINFO_BASENAME);

        if (!$set_default_banner) {
			$this->load->library(array('cmsbanner_library'));  
			$cmsBannerName = $pathinfo;
            $fileType = substr($pathinfo, strrpos($pathinfo, '.') + 1);
            if (!empty($pathinfo2) && strrpos($pathinfo2, '.') > 0) {
                $fileType = substr($pathinfo2, strrpos($pathinfo2, '.') + 1);
				$cmsBannerName = $pathinfo2;
            }
    
            if (isset($_FILES['userfile']) && !empty($_FILES['userfile']['name'])) {
                $cmsBannerName = $this->cmsbanner_library->uploadBannerImage('userfile');

                if (false === $cmsBannerName) {
                    $message = $this->upload->display_errors('', '');
                    $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
                }
            } else {
                if(!$this->cmsbanner_library->allowUploadFormat($fileType)) {

                    $is_default_banner_flag = true;
                }
            }
        }



        $data = array(
                    'title' => htmlspecialchars($title),
                    'content' => nl2br(htmlspecialchars($content)),
                    'categoryId' => $categoryId,
                    'creator_user_id' => $this->authentication->getUserId(),
                    'is_daterange' => ($isDateRange && $start_date && $end_date) ? 1 : 0,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    // 'set_visible' => 0,
                    'display_in' => json_encode($display_in)?:null,
                    'display_freq' => json_encode($display_freq)?:null,
                    'redirect_to'=> $redirect_to,
                    'redirect_btn_name'=> $redirect_btn_name,
                    'redirect_type' =>$redirect_type,
                    'is_default_banner'=> $is_default_banner_flag == 'true'? true: false,
                    'banner_url'=> isset($cmsBannerName)? $cmsBannerName : $banner_url,
                );
		return $data;
    }
}
