<?php 

namespace local_newsvnr\api\controllers\ebm;

use stdClass;
use local_newsvnr\api\controllers\BaseController as BaseController;

defined('MOODLE_INTERNAL') || die;

class CourseController extends BaseController {

	private $table = 'course';

	public $data;
	public $resp;

	public function __construct($container) {
		global $CFG;
		parent::__construct($container);
		if(isloggedin()) {
            $CFG->sessiontimeout += 7200;
        } else {
            $adminuser = get_complete_user_data('id', 2);
            complete_user_login($adminuser);
        }
   		$this->data = new stdClass;
   		$this->resp = new stdClass;
   		
   	}

   	public function validate() {
        //Khai báo  rules cho validation
        $this->validate = $this->validator->validate($this->request, [
            'fullname' => $this->v::notEmpty()->notBlank(),
            'shortname' => $this->v::notEmpty()->notBlank(),
            // 'startdate' => $this->v::notEmpty()->notBlank(),
            // 'enddate' => $this->v::notEmpty()->notBlank(),
            'categoryname' => $this->v::notEmpty()->notBlank(),
            'categorycode' => $this->v::notEmpty()->notBlank(),
            'teachercode' => $this->v::notEmpty()->notBlank(),
            'pagename' => $this->v::notEmpty()->notBlank(),
            'pagecode' => $this->v::notEmpty()->notBlank(),
            'pageintro' => $this->v::notEmpty()->notBlank(),
            'usercode' => $this->v::notEmpty()->notBlank(),
        ]);
    }

	public function create_and_update($request, $response, $args) {
		global $DB, $CFG;
		require_once("$CFG->dirroot/course/lib.php");
		require_once("$CFG->dirroot/local/newsvnr/lib.php");
		$this->validate();
      	if ($this->validate->isValid()) {
	    	$this->data->fullname = $request->getParam('fullname');
		    $this->data->shortname = $request->getParam('shortname');
		    // $this->data->coursesetup = $request->getParam('setupcode');
		    $this->data->categoryname = $request->getParam('categoryname');
		    $this->data->categorycode = $request->getParam('categorycode');
		    $this->data->startdate = $request->getParam('startdate');
		    $this->data->enddate = $request->getParam('enddate');
		    if($request->getParam('startdate') == '') 
		    	$this->data->startdate = time();
		    else
		    	$this->data->startdate = strtotime($request->getParam('startdate'));
		    if($request->getParam('enddate'))
		    	$this->data->enddate = strtotime($request->getParam('enddate'));
		    else 
		    	$this->data->enddate = 0;
		    $this->data->teachercode = $request->getParam('teachercode');
		    $this->data->pagename = $request->getParam('pagename');
		    $this->data->pagecode = $request->getParam('pagecode');
		    $this->data->pageintro = $request->getParam('pageintro');
		    $this->data->usercode = $request->getParam('usercode');
		    $this->data->idnumber = '';
			$this->data->format = 'topics';
			$this->data->showgrades = 1;
			$this->data->numsections = 4;
			$this->data->newsitems = 10;
			$this->data->visible = 1;
			$this->data->showreports = 1;
			$this->data->summary = '';
			$this->data->summaryformat = FORMAT_HTML;
			$this->data->lang = 'vi';
			$this->data->typeofcourse = 3;
			$this->data->enablecompletion = 1;	

	    } else {
        	$errors = $this->validate->getErrors();
        	$this->resp->error = true;
        	$this->resp->data[] = $errors;
	        return $response->withStatus(422)->withJson($this->resp);
	    }
		
		$courseid = $DB->get_field('course', 'id', ['fullname' => $this->data->fullname, 'shortname' => $this->data->shortname]);
		$userid = find_usercode_by_code($this->data->usercode);
		$teacherid = find_usercode_by_code($this->data->teachercode);
		if(!$userid) {
			$this->resp->data['usercode'] = "Mã học viên không tồn tại";
		}
		if(!$teacherid) {
			$this->resp->data['teachercode'] = "Mã giáo viên không tồn tại";
		}
		if($courseid) {
			$this->data->id = $courseid;
			$course = $DB->get_record($this->table, ['id' => $courseid]);
			if (!empty($this->data->shortname) && $course->shortname !== $this->data->shortname ) {
	            $this->check_code = $DB->get_record($this->table,['shortname' => $this->data->shortname], 'shortname');
				if($this->check_code) {
					$check_code = $this->check_code->shortname;
					$this->resp->data['code'] = "Mã khoá học'$check_code' đã tồn tại";
				}
	        }
	        if($this->data->categoryname and $this->data->categorycode) {
				$existing = $DB->get_field('course_categories','id',['name' => $this->data->categoryname, 'idnumber' => $this->data->categorycode]);
				if($existing) {
					$this->data->category = $existing;
				} else {
					$categoryname = $this->data->categoryname;
					$this->resp->data['categoryname'] = "Không tìm thấy tên '$categoryname' trong danh mục khoá học ";
				}
			} else {
				$this->resp->data['category'] = "Thiếu 'categoryname' hoặc 'categorycode";
			}
			if(empty($this->resp->data)) {
				
				try {
					update_course($this->data);
					$modinfo = new stdClass;
				    $modinfo->name = $this->data->pagename;
				    $modinfo->code = $this->data->pagecode;
				    $modinfo->modulename = 'page';
				    $modinfo->course = $courseid;
				    $modinfo->section = 1;
				    $modinfo->visible = 1;
				    $modinfo->display = 5;
				    $modinfo->completion = 2;
        			$modinfo->completionview = 1;
				    $modinfo->printheading = '1';
				    $modinfo->printintro = '0';
				    $modinfo->printlastmodified = '1';
				    $modinfo->introeditor = ['text' => '', 'format' => '1', 'itemid' => 0];
					$pageid = $DB->get_field('page', 'id', ['course' => $courseid, 'name' => $this->data->pagename]);
					if($pageid) {
						$cm = get_coursemodule_from_instance('page', $pageid);
						$modinfo->id = $pageid;
						$modinfo->revision = 0;
						$modinfo->page = ['text' => $this->data->pageintro,'format' => '1', 'itemid' => 0];
						$modinfo->coursemodule = $cm->id;
						$modulepage = update_module($modinfo);
					} else {
						// $modinfo->page = ['text' => $this->data->pageintro,'format' => '1', 'itemid' => 0];
						$modinfo->content = $this->data->pageintro;
						$modinfo->intoformat = 1;
						$modulepage = create_module($modinfo);
					}
					
				    $this->resp->error = false;
					$this->resp->message['info'] = "Chỉnh sửa buổi học thành công";
					$enrol_user = check_user_in_course($courseid,$userid);
					$enrol_teahcer = check_teacher_in_course($courseid,$teacherid);

				    if(!$enrol_user) {
				    	enrol_user($userid, $courseid, 'student');
				    	$this->resp->error = false;
						$this->resp->message['info'] = "Thêm thành công thêm user vào khóa học";
				    } else {
				    	$this->resp->error = false;
				    	$this->resp->message['info'] = "Học viên đã tham gia vào khóa";
				    }
				    if(!$enrol_teahcer) {
				    	enrol_user($teacherid, $courseid, 'editingteacher');
				    	$this->resp->error = false;
						$this->resp->message['info'] = "Thêm thành công thêm user vào khóa học";
				    } else {
				    	$this->resp->error = false;
				    	$this->resp->message['info'] = "Giáo viên đã tham gia vào khóa";
				    }
					$this->resp->error = false;
					$this->resp->message['info'] = "Chỉnh sửa thành công";
					$this->resp->data[] = $this->data;

				} catch (Exception $e) {
					$this->resp->error = true;
					$this->resp->data->message['info'] = "Chỉnh sửa thất bại với lỗi: $e->getMessage()";
				}		
			} else {
				$this->resp->error = true;
			}
		} else {

			if($this->data->categoryname and $this->data->categorycode) {
				$existing = $DB->get_field('course_categories','id',['name' => $this->data->categoryname, 'idnumber' => $this->data->categorycode]);
				if($existing) {
					$this->data->category = $existing;
				} else {
					$categoryname = $this->data->categoryname;
					$this->resp->data['categoryname'] = "Không tìm thấy tên '$categoryname' trong danh mục khoá học ";
				}
			} else {
				$this->resp->data['category'] = "Thiếu 'categoryname' hoặc 'categorycode";
			}

			if (!empty($this->data->shortname)) {
	            $this->check_code = $DB->get_record($this->table,['shortname' => $this->data->shortname], 'shortname');
				if($this->check_code) {
					$check_code = $this->check_code->shortname;
					$this->resp->data['code'] = "Mã khoá học '$check_code' đã tồn tại!";
				}
	        }
	   
			if(empty($this->resp->data)) {
				
				try {
					$course = create_course($this->data);
					if($course) {
						$modinfo = new stdClass;
					    $modinfo->name = $this->data->pagename;
					    $modinfo->code = $this->data->pagecode;
					    $modinfo->modulename = 'page';
					    $modinfo->course = $course->id;
					    $modinfo->section = 1;
					    $modinfo->visible = 1;
					    $modinfo->display = 5;
					    $modinfo->completion = 2;
        				$modinfo->completionview = 1;
					    $modinfo->printheading = '1';
					    $modinfo->printintro = '0';
					    $modinfo->printlastmodified = '1';
					    $modinfo->content = $this->data->pageintro;
					    $modinfo->introeditor = ['text' => '', 'format' => '1', 'itemid' => 0];
					    $modinfo->contentformat = 1;
						$modinfo->intoformat = 1;
					    $modulepage = create_module($modinfo);
					    $this->resp->error = false;
						$this->resp->message['info'] = "Tạo mới buổi học thành công";
						$this->resp->data[] = $modulepage;
					} 
					$enrol_user = check_user_in_course($course->id,$userid);
					$enrol_teahcer = check_teacher_in_course($course->id,$teacherid);

				    if(!$enrol_user) {
				    	enrol_user($userid, $course->id, 'student');
				    	$this->resp->error = false;
						$this->resp->message['info'] = "Thêm thành công thêm user vào khóa học";
				    } else {
				    	$this->resp->error = false;
				    	$this->resp->message['info'] = "Học viên đã tham gia vào khóa";
				    }
				    if(!$enrol_teahcer) {
				    	enrol_user($teacherid, $course->id, 'editingteacher');
				    	$this->resp->error = false;
						$this->resp->message['info'] = "Thêm thành công thêm user vào khóa học";
				    } else {
				    	$this->resp->error = false;
				    	$this->resp->message['info'] = "Giáo viên đã tham gia vào khóa";
				    }
					$this->resp->error = false;
					$this->resp->message['info'] = "Tạo mới thành công";
					$this->resp->data[] = $this->data;

				} catch (Exception $e) {
					$this->resp->error = true;
					$this->resp->data->message['info'] = "Tạo mới thất bại với lỗi: $e->getMessage()";
				}		
			} else {
				$this->resp->error = true;
			}
		}
		
		return $this->response->withStatus(200)->withJson($this->resp);
	}

	/**
	 * API rút tên học viên khỏi khoá học EBM
	 * @param  [type] $request  [description]
	 * @param  [type] $response [description]
	 * @param  [type] $args     [description]
	 * @return [type]           [description]
	 */
	public function unenrol_user($request, $response, $args) {
		global $DB,$CFG;
		require_once($CFG->dirroot . '/enrol/locallib.php');
		$this->validate = $this->validator->validate($this->request, [
            'usercode' => $this->v::notEmpty()->notBlank()->noWhitespace(),
            'coursecode' => $this->v::notEmpty()->notBlank(),
            // 'orgjobtitlecode' => $this->v::notEmpty()->notBlank(),
            // 'orgstructurecode' => $this->v::notEmpty()->notBlank(),
        ]);

		if($this->validate->isValid()) {
			$this->data->usercode = $request->getParam('usercode');
			$this->data->shortname = $request->getParam('coursecode');
		} else {
			$errors = $this->validate->getErrors();
        	$this->resp->error = true;
        	$this->resp->data[] = $errors;
	        return $response->withStatus(422)->withJson($this->resp);
		}
		
		$courseid = $DB->get_field('course', 'id', ['shortname' => $this->data->shortname]);
		if(!$courseid) {
			$this->resp->error = true;
			$this->resp->data['shortname'] = "shortname(Mã khoá học) không tồn tại khoá học";
		}
		
		if(!$DB->record_exists('user',['usercode' => $this->data->usercode])) {
			$this->resp->error = true;
			$this->resp->data['usercode'] = "usercode(Mã học viên) không tồn tại";
		}

		if(empty($this->resp->data)) {
			$usercode = $this->data->usercode;
			$course = $DB->get_record('course', ['shortname' => $this->data->shortname]);
			if(!$course) {
				$this->resp->message['info'] = "Ứng viên với mã '$usercode' chưa tham gia khóa học";
			} else {
				$get_userid = find_usercode_by_code($usercode);
				if($get_userid) {
					$instance = $DB->get_record('enrol', array('courseid'=> $course->id, 'enrol'=>'manual'), '*', MUST_EXIST);
					$get_ueid = find_ueid_by_enrolid($instance->id,$get_userid);
					$plugin = enrol_get_plugin($instance->enrol);
					$plugin->unenrol_user($instance, $get_ueid->userid);
					$this->resp->error = false;
					$this->resp->message['info'] = "Rút ứng viên với mã '$usercode' từ khóa '$course->fullname' thành công";
				}
			}
				
		}
		return $response->withStatus(200)->withJson($this->resp);
	}

	public function delete($request, $response, $args) {
		global $DB, $CFG;
		require_once($CFG->dirroot . '/course/lib.php');
		$this->validate = $this->validator->validate($this->request, [
            'coursecode' => $this->v::notEmpty()->notBlank(),
        ]);

		if($this->validate->isValid()) {
			$this->data->shortname = $request->getParam('coursecode');
		} else {
			$errors = $this->validate->getErrors();
        	$this->resp->error = true;
        	$this->resp->data[] = $errors;
	        return $response->withStatus(422)->withJson($this->resp);
		}
		$courses = $DB->get_record('course', ['shortname' => $this->data->shortname], 'id');
		if(!$courses) {
			$this->resp->error = true;
        	$this->resp->data['coursecode'] = 'Mã lớp học không tồn tại';
		}
		if(empty($this->resp->data)) {
			$warnings = array();
	        foreach ($courses as $courseid) {
	            $course = $DB->get_record('course', array('id' => $courseid));

	            if ($course === false) {
	                $warnings[] = array(
	                                'item' => 'course',
	                                'itemid' => $courseid,
	                                'warningcode' => 'unknowncourseidnumber',
	                                'message' => 'Unknown course ID ' . $courseid
	                            );
	                continue;
	            }

	            // Check if the context is valid.
	            // $coursecontext = context_course::instance($course->id);
	            // self::validate_context($coursecontext);

	            // Check if the current user has permission.
	            if (!can_delete_course($courseid)) {
	                $warnings[] = array(
	                                'item' => 'course',
	                                'itemid' => $courseid,
	                                'warningcode' => 'cannotdeletecourse',
	                                'message' => 'You do not have the permission to delete this course' . $courseid
	                            );
	                continue;
	            }

	            if (delete_course($course, false) === false) {
	                $warnings[] = array(
	                                'item' => 'course',
	                                'itemid' => $courseid,
	                                'warningcode' => 'cannotdeletecategorycourse',
	                                'message' => 'Course ' . $courseid . ' failed to be deleted'
	                            );
	                continue;
	            }
	        }

	        fix_course_sortorder();
	        if(empty($warnings)) {
	        	$this->resp->error = false;
	        	$this->resp->message['info'] = "Xóa lớp học thành công";
	        }
		}
        
        return $response->withStatus(200)->withJson($this->resp);
	}
	
}