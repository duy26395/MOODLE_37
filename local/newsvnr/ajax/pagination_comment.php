<?php


require_once (__DIR__ . '/../../../config.php');

require_once (__DIR__ . '/../lib.php');

global $DB;


$discussionid = $_GET['discussionid'];
	
$currentPage = optional_param('page', 0 ,PARAM_INT);

$itemInPage = 5;


if($currentPage == 1) {
	$from  = $currentPage * $itemInPage; 
}
else { 
	$from  = $currentPage * $itemInPage; 
}

$get_comment = pagination_comment($discussionid, $from ,$itemInPage);


$xhtml = "";
if(!empty($get_comment))
{


	foreach ($get_comment as $key => $comment) {

		$get_reply = get_replies_from_comment($comment->id);

			$html_reply = "";
			foreach ($get_reply as $key => $reply) {

				if($comment->id == $reply->commentid)
				{

					$html_reply .= '           

	                            <div class="chat-reply" id="reply_'. $reply->id .'">
	                                <!-- Comment 1 -->
	                                <div class="col chat-panel">
	                                    <div class="chat-image">
	                                        <img class="rounded-circle" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6IMTq-efHer8sp1p23DxIw_NsFFUtc6ZI0vAexxMm0MPEsii-" />
	                                    </div>
	                                    <div class="chat-content">
	                                        <div class="chat-body">
	                                            <h3 class="name">'. $reply->fullname .'</h3>
	                                             <div style="margin-left:30px;color: grey"><label class="date-feedback">'. convertunixtime(' d-m-Y H:i A', $reply->createdat, 'Asia/Ho_Chi_Minh') .'</label></div>
	                                            
	                                        </div>
	                                        <p>'. $reply->content .'</p>
	                                        <div class="chat-footer">
	                                            <label class="like">Like</label>
	                                            <label class="delete_reply" 
	                                                            onclick="DeleteReply('. $reply->id .')" id="'. $reply->id .'">Xóa</label>  
	                                             <input type="hidden" id="delete_reply'. $reply->id .'" name="" value="delete" />
	                                           
	                                        </div>
	                                    </div>
	                                </div>

	                            </div>';
				}
				else{
					break;
				}
					
			}


			$xhtml .=  '<div class="row">

			                <div class="col chat-panel" id="comment_'. $comment->id .'">
			                    <div class="chat-image">
			                        <img class="rounded-circle" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6IMTq-efHer8sp1p23DxIw_NsFFUtc6ZI0vAexxMm0MPEsii-" />
			                    </div>

			                    <div class="chat-content">
			                        <div class="chat-body">
			                            <h3 class="name">'. $comment->fullname .'</h3>
			                            <div style="margin-left:30px;color: grey"><label class="date-feedback">'. convertunixtime(' d-m-Y H:i A', $reply->createdat, 'Asia/Ho_Chi_Minh') .'</label></div>
			                            
			                        </div>
			                        	<p>'. $comment->content .'</p>
			                          <div class="chat-footer">
			                             <div class="chat-footer">
			                                <label class="like" id="'. $comment->id .'">Like</label>

			                                <label class="delete delete_comment"  onclick="DeleteComment('. $comment->id .')" id="{{{id}}}">'. get_string('delete') .'</label>
			                                <input type="hidden" id="delete_comment'. $comment->id .'"  value="delete">

			                                <label class="feedback" id="'. $comment->id .'" onclick="FeedBack('. $comment->id .')">'. get_string('feedback', 'local_newsvnr') .'</label>
			      
			                            </div>

			                            <!-- ACTION SHOW AND HIDE REPLIES -->
			                                           <p class="chat-show-reply" id="show-reply'. $comment->id .'" 
	                                    onclick="ShowReplies('. $comment->id  .')" ><i class="fa fa-chevron-down"> '. get_string('showfeedback', 'local_newsvnr') .'</i></p>

	                                    <p style="display: none;" class="chat-hidden-reply" id="hidden-reply'. $comment->id  .'"
	                                     onclick="HiddenReplies('. $comment->id  .')"><i class="fa fa-chevron-up"> '. get_string('hidefeedback', 'local_newsvnr') .'</i></p>

			                            <!-- ACTION END SHOW AND HIDE REPLIES --> 
			                            <div class="new-detail-reply-body form-reply" style="width: 80%; display: none;">
			                            	<form>
				                                <label class="new-detail-reply-title">'. get_string('comment', 'local_newsvnr') .'</label>

				                                <textarea class="new-detail-reply-content" name="content_reply" id="content_reply" placeholder="'. get_string('yourcomment', 'local_newsvnr') .'"></textarea>
				       
					                                <input type="hidden" id="commentid" name="" value="'. $comment->id .'"> 
					                                <input type="hidden" id="userid" name="userid" value="'. $comment->userid .'" /> 
					                                <input type="hidden" id="fullname" value="'. $comment->fullname .'" name="" />
				                 
				                                <div class="new-detail-reply-control">
				                                    <button type="button" class="btn btn-cancel">'. get_string('cancel') .'</button>

				                                    <button type="button" id="post_reply" name="post_reply" class="btn btn-submit ">'. get_string('sendcomment', 'local_newsvnr') .'</button>

				                                </div>
				                             </form>
			                            </div>

			                            <div class="clearfix"></div>

			                            <div class="list-reply'. $comment->id .'" id="list_reply'. $comment->id .'" style="display: none; overflow: hidden;" >
			                             	'. $html_reply .'
			                            </div>
			                        </div>

			                    </div>
			                </div>

			            </div>
					';		
	}
	

	echo $xhtml;				
}
else{
	echo "";
}




