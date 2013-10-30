 /**
 * @Original_author axisJ Javascript Library (tom@axisj.com)
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief HTML5 file uploader based on AXISJ(www.axisj.com) AXUpload5
 */
var uploadedFiles    = [];
var uploaderSettings = [];
var loaded_images    = [];
var swfUploadObjs    = [];
var uploadSettingObj = [];
var uploadAutosaveChecker = false;
var $ = jQuery.noConflict();

// NuriCms: AXUpload5의 추가 객체생성
var AXUpload5 = Class.create(AXUpload5, {
	custom: {
		axDeleteQueue: 0, // 삭제 발생시의 큐
		reloadFileList: function(cfg) { // 서버로부터 파일리스트 요청(임시저장 처리용)
			var params = {
				mid : current_mid,
				file_list_area_id : cfg.fileListAreaID,
				editor_sequence   : cfg.editorSequence,
				upload_target_srl : cfg.uploadTargetSrl
			};

			// 최초 한번만 동작
			if(!uploadAutosaveChecker){
				exec_xml(
					'file',
					'getFileList',
					params,
					myUpload.custom.on_complete,
					'error,message,files,upload_status,upload_target_srl,editor_sequence,left_size'.split(',')
				);
			}
		},
		autosave: function() {
			if(typeof(_editorAutoSave) != 'function') return;
			uploadAutosaveChecker = true;
			_editorAutoSave(true);
		},
		on_complete: function(ret, response_tags) {
			var $list, seq, files, target_srl, up_status, remain, items, i, c, itm, file_srl, file_srls;

			seq   = ret.editor_sequence;
			files = ret.files;
			up_status  = ret.upload_status;
			target_srl = ret.upload_target_srl;
			remain = Math.floor((parseInt(ret.left_size,10)||0)/1024);

			if(target_srl) {
				if(editorRelKeys[seq].primary.value != target_srl) {
					editorRelKeys[seq].primary.value = target_srl;
					myUpload.custom.autosave();
				}

				editorRelKeys[seq].primary.value = target_srl;
			}
	
			// 문서 강제 자동저장 1번만 사용 ( 첨부파일 target_srl로 자동 저장문서를 저장하기 위한 용도일 뿐 )
			if(!uploadAutosaveChecker) myUpload.custom.autosave();
		},
		insertUploadedFile: function(editorSequence, files) { // 본문 삽입
			var settings = uploadSettingObj[editorSequence],
				fileListAreaID = settings.fileListAreaID,
				targetFiles,
				targetfileID = [],
				uploadFile = [],
				text = new Array();
	
			if(editorMode[editorSequence]=='preview') return;

			// 본문 삽입의 대상을 첨부파일 리스트로부터 구함
			if(files == undefined) { 
				targetFiles = myUpload.multiSelector.getSelects();
				if(targetFiles.length < 1) return false;
	
				$.each(targetFiles, function(i, file){
					targetfileID[file.id] = file.id;
				});
	
				$.each(myUpload.uploadedList, function(i, file){
					if(!targetfileID[file.id]) return true;
					uploadFile.push(file);
				});
			} else {
				uploadFile.push(files);
			}
	
			editorFocus(editorSequence);

			$.each(uploadFile, function(){
				if(!this.file_srl) return true;
				// 바로 링크 가능한 파일의 경우 (이미지, 플래쉬, 동영상 등..)
				if(this.direct_download == 'Y') {
					// 이미지 파일의 경우 image_link 컴포넌트 열결
					if(this.download_url == undefined) this.download_url = this.uploaded_filename;
					if(/\.(jpg|jpeg|png|gif)$/i.test(this.download_url)) {
						if(loaded_images[this.file_srl]) {
							var obj = loaded_images[this.file_srl];
						}
						else {
							var obj = new Image();
							obj.src = this.download_url;
						}
						temp_code = '';
						temp_code += "<img src=\""+this.download_url+"\" alt=\""+this.source_filename+"\"";
						if(obj.complete == true) { temp_code += " width=\""+obj.width+"\" height=\""+obj.height+"\""; }
						temp_code += " />\r\n";
						text.push(temp_code);
					// 이미지외의 경우는 multimedia_link 컴포넌트 연결
					} else {
						text.push("<img src=\"common/img/blank.gif\" editor_component=\"multimedia_link\" multimedia_src=\""+this.download_url+"\" width=\"400\" height=\"320\" style=\"display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;\" auto_start=\"false\" alt=\"\" />");
					}
		
				// binary파일의 경우 url_link 컴포넌트 연결
				} else {
					text.push("<a href=\""+this.download_url+"\">"+this.source_filename+"</a>\n");
				}
			});
	
			// html 모드
			if(editorMode[editorSequence]=='html'){
				if(text.length>0 && get_by_id('editor_textarea_'+editorSequence))
				{
					get_by_id('editor_textarea_'+editorSequence).value += text.join('');
				}
	
			// 위지윅 모드
			}else{
				var iframe_obj = editorGetIFrame(editorSequence);
				if(!iframe_obj) return;
				if(text.length>0) editorReplaceHTML(iframe_obj, text.join(''));
			}
		}
	},
	uploadQueue: function(){ // 업로드시 파일박스 리스트 추가
		var cfg = this.config;
		if(!this.queueLive) return;
		if(this.queue.length == 0){
			//trace("uploadEnd");
			this.uploadComplete();
			return;
		}
		
		var uploadQueue = this.uploadQueue.bind(this);
		var cancelUpload = this.cancelUpload.bind(this);
		var uploadSuccess = this.uploadSuccess.bind(this);
		var onClickDeleteButton = this.onClickDeleteButton.bind(this);
		var onClickFileTitle = this.onClickFileTitle.bind(this);
		
		var obj = this.queue.shift();
		this.uploadingObj = obj;
		var formData = new FormData();
		//서버로 전송해야 할 추가 파라미터 정보 설정
		$.each(cfg.uploadPars, function(k, v){
			formData.append(k, v); 
		});
		//formData.append(obj.file.name, obj.file);
		formData.append(cfg.uploadFileName, obj.file);
		
		//obj.id
		var itemID = obj.id;
		
		this.xhr = new XMLHttpRequest();
		this.xhr.open('POST', cfg.uploadUrl, true);
		this.xhr.onload = function(e) {
			var res = e.target.response;
			try { if (typeof res == "string") res = res.object(); } catch (e) {
				trace(e);
				cancelUpload();
				return;
			}

			if (res.result == "err" || res.error) {
				cfg.onError("res_error");
				trace(res);
				$("#" + itemID).fadeOut("slow");
				cancelUpload();
				return;
			}
			
			if(cfg.isSingleUpload){
				
				$("#"+itemID+" .AXUploadBtns").show();
				$("#"+itemID+" .AXUploadLabel").show();
				$("#"+itemID+" .AXUploadTit").show();
				
				$("#"+itemID+" .AXUploadProcess").hide();
								
				uploadSuccess(obj.file, itemID, res);
				// --------------------- s
				$("#"+itemID+" .AXUploadBtnsA").bind("click", function(){
					onClickDeleteButton(itemID);
				});
				if(cfg.onClickUploadedItem){
					$("#"+itemID+" .AXUploadDownload").bind("click", function(){
						onClickFileTitle(itemID);
					});
				}
				
			}else{
				
//				$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadBtns").show();
				$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadLabel").show();
				$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadProcess").hide();

				if(!res[cfg.fileKeys.thumbPath]) res[cfg.fileKeys.thumbPath] = res[cfg.fileKeys.uploaded_filename]; // NuriCms: 섬네일 이미지가 없으면 기본 주소 호출

				if(/\.(jpg|jpeg|png|gif)$/i.test(res[cfg.fileKeys.thumbPath])){ // NuriCms: 이미지 확장자구분
					$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadIcon").css({
						"background-image":"url('"+(res[cfg.fileKeys.thumbPath]||"").dec()+"')"
					}).addClass("AXUploadPreview");
				}else{
					$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadIcon").css({"background-image":"url()"});
						$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadIcon").html((
							res[cfg.fileKeys.name].substring(res[cfg.fileKeys.name].lastIndexOf('.')+1,res[cfg.fileKeys.name].length).toLowerCase()
							||"none"
						).dec().replace(".", "")); // NuriCms: 일반 파일 확장자명 추가
				}
				
				uploadSuccess(obj.file, itemID, res);
				// --------------------- s
				$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadBtnsA").bind("click", function(){
					onClickDeleteButton(itemID);
				});
				if(cfg.onClickUploadedItem){
					$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadDownload").bind("click", function(){
						onClickFileTitle(itemID);
					});
				}
			}

			// --------------------- e
			uploadQueue();
		};
		var setUploadingObj = function(){
			this.uploadingObj = null;
		};
		var setUploadingObjBind = setUploadingObj.bind(this);
		this.xhr.upload.onprogress = function(e) {
			if(cfg.isSingleUpload){
				if (e.lengthComputable) { $("#"+itemID).find(".AXUploadProcessBar").width( ((e.loaded / e.total) * 100).round(2)+"%" ); }	
			}else{
				if (e.lengthComputable) { $("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadProcessBar").width( ((e.loaded / e.total) * 100).round(2)+"%" ); }	
			}
			if (e.lengthComputable) {
				if(	e.loaded > e.total*0.9 ){
					setUploadingObjBind();
				}
			}
		};
		this.xhr.send(formData);  // multipart/form-data
	},
	setUploadedList: function(files){ // 파일박스 리스트 추가
		var cfg = this.config;
		
		var getItemTag = this.getItemTag.bind(this);
		var onClickDeleteButton = this.onClickDeleteButton.bind(this);
		var onClickFileTitle = this.onClickFileTitle.bind(this);
		
		if(cfg.isSingleUpload){

			var f;
			if($.isArray(files)){
				this.uploadedList.push(files.first());
				f = files.first();
			}else{
				this.uploadedList.push(files);
				f = files;
			}
			if(!f) return;
			var itemID = f.id;
			
			var uf = {
				id:itemID,
				name:f[cfg.fileKeys.name],
				size:f[cfg.fileKeys.fileSize]
			};
			
			$("#" + cfg.targetID+'_AX_display').empty();
			$("#" + cfg.targetID+'_AX_display').append(this.getItemTag(itemID, uf));
			
			$("#"+itemID+" .AXUploadBtns").show();
			$("#"+itemID+" .AXUploadLabel").show();
			$("#"+itemID+" .AXUploadTit").show();
			$("#"+itemID+" .AXUploadProcess").hide();
			
			$("#"+itemID+" .AXUploadBtnsA").bind("click", function(){
				onClickDeleteButton(itemID);
			});
			if(cfg.onClickUploadedItem){
				$("#"+itemID+" .AXUploadDownload").bind("click", function(){
					onClickFileTitle(itemID);
				});
			}
			
		}else{
			this.uploadedList = files;
			if(cfg.queueBoxID){
				$.each(this.uploadedList, function(fidx, f){
					if(f.id == undefined){
						trace("id key is required.");
						return false;	
					}
					var itemID = f.id;
					var uf = {
						id:itemID,
						name:f[cfg.fileKeys.name],
						size:f[cfg.fileKeys.fileSize]
					};
					$("#" + cfg.queueBoxID).prepend(getItemTag(itemID, uf));
					$("#" + cfg.queueBoxID).find("#"+itemID).fadeIn();

					// --------------------- s
//					$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadBtns").show(); // 삭제버튼은 비표시
					$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadLabel").show();
					$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadProcess").hide();

					if(!f[cfg.fileKeys.thumbPath]) f[cfg.fileKeys.thumbPath] = f[cfg.fileKeys.download_url]; // NuriCms: 섬네일 이미지가 없으면 기본 주소 호출\

					if(/\.(jpg|jpeg|png|gif)$/i.test(f[cfg.fileKeys.thumbPath])){ // NuriCms: 이미지 확장자구분
						$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadIcon").css({
							"background-image":"url('"+(f[cfg.fileKeys.thumbPath]||"").dec()+"')"
						}).addClass("AXUploadPreview");
					}else{
						$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadIcon").css({"background-image":"url()"});
						$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadIcon").html((
							f[cfg.fileKeys.name].substring(f[cfg.fileKeys.name].lastIndexOf('.')+1,f[cfg.fileKeys.name].length).toLowerCase()
							||"none"
						).dec().replace(".", "")); // NuriCms: 일반 파일 확장자명 추가
					}
		
					$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadBtnsA").bind("click", function(){
						onClickDeleteButton(itemID);
					});
					if(cfg.onClickUploadedItem){
						$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadDownload").attr("title", uploadSettingObj[editorSequence].lang.uploadButtonEditor); // NuriCms: 본문 첨부설명
						$("#" + cfg.queueBoxID).find("#"+itemID+" .AXUploadDownload").bind("click", function(){
							onClickFileTitle(itemID);
						});
					}
					// --------------------- e

					$("#"+itemID).addClass("readyselect");
				});
				this.multiSelector.collect();
			}
		}
	},
	onFileDragOver: function(evt){ // AXUpload5 onFileDragOver (버그 처리를 위해 선언)
		var cfg = this.config;
		$("#"+cfg.dropBoxID).addClass("onDrop");
		$("#"+cfg.dropBoxID+"_dropZoneBox").show();

		/*$("#"+cfg.dropBoxID+"_dropZoneBox").css({height:$("#"+cfg.dropBoxID).innerHeight()-6, width:$("#"+cfg.dropBoxID).innerWidth()-6}); 라르게덴 2013-10-29 오후 3:21:45 */
		$("#"+cfg.dropBoxID+"_dropZoneBox").css({height:$("#"+cfg.dropBoxID).prop("scrollHeight")-6, width:$("#"+cfg.dropBoxID).innerWidth()-6});

		var dropZone = document.getElementById(cfg.dropBoxID+"_dropZoneBox");
		dropZone.addEventListener('dragleave', function(evt){
			$("#"+cfg.dropBoxID).removeClass("onDrop");
			$("#"+cfg.dropBoxID+"_dropZoneBox").hide();
		}, false);

		evt.stopPropagation();
		evt.preventDefault();
		evt.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
	},
	deleteFile: function(file, onEnd){ // AXUpload5 deleteFile
		var cfg = this.config;
		if(!onEnd) if(!confirm(AXConfig.AXUpload5.deleteConfirm)) return;
		var removeUploadedList = this.removeUploadedList.bind(this);

		if (file != undefined){
			var pars = [];
			var sendPars = "";
			$.each(file, function(k, v){
				pars.push(k + '=' + v);
			});
			
			if (typeof(cfg.deletePars) === "object") {
				$.each(cfg.deletePars, function(k, v){
					pars.push(k + '=' + v);
				});
				sendPars = pars.join("&");
			}else{
				sendPars = pars.join("&") + "&" + cfg.deletePars;
			}

			if(cfg.isSingleUpload){
				$("#"+file.id+" .AXUploadBtns").hide();
			}else{
				$("#" + cfg.queueBoxID).find("#"+file.id+" .AXUploadBtns").hide();
			}
			new AXReq(cfg.deleteUrl, {debug:false, pars:sendPars, contentType:"application/json", onsucc:function(res){
				if(res.message == AXConfig.AXReq.okCode){
					if(cfg.isSingleUpload){
						$('#'+cfg.targetID+'_AX_display').html(AXConfig.AXUpload5.uploadSelectTxt);
					}else{
						$("#"+file.id).hide(function(){
							$(this).remove();
						});
					}

					removeUploadedList(file.id);

					// NuriCms: 서버에서 리턴받은 값과 로컬에 저장된 값을 통합해서 onDelete로 보냄
					var response_tags = {
						res:res,
						file:file
					};
					if(cfg.onDelete) cfg.onDelete.call(response_tags, response_tags);
					if(onEnd) onEnd();

					// NuriCms: delete 큐가 종료 될때 onComplete를 호출
					myUpload.custom.axDeleteQueue -= 1;
					if(myUpload.custom.axDeleteQueue < 1) {
						if(cfg.onComplete) cfg.onComplete.call(response_tags, response_tags);
					}
				}else{
					$("#" + cfg.queueBoxID).find("#"+file.id+" .AXUploadBtns").show();
				}
			}});

		}else{
			trace("file undefined");
		}
	},
	deleteSelect: function(arg){ // AXUpload5 deleteSelect
		if(arg == "all"){
			if(!confirm(AXConfig.AXUpload5.deleteConfirm)){ // NuriCms: 삭제전 확인
				return false;
			}

			var deleteQueue = [];
			
			 // NuriCms: 삭제 대상이 없으면 메시지를 출력
			if(this.uploadedList.length == 0){
				toast.push({body:uploadSettingObj[editorSequence].lang.error_deleteQueue, type:'Warning'});
			}
			$.each(this.uploadedList, function(){
				deleteQueue.push(this.id);
			});
			this.ccDelete(deleteQueue, 0);
			myUpload.custom.axDeleteQueue = deleteQueue.length; // NuriCms: delete 큐 생성
			deleteQueue = null;
		}else{
			if(!this.multiSelector) toast.push({body:uploadSettingObj[editorSequence].lang.msg_file_cart_is_null, type:'Warning'}); // NuriCms: 삭제 대상이 없으면 메시지를 출력
			var selectObj = this.multiSelector.getSelects();
			if (selectObj.length > 0){
				if(!confirm(AXConfig.AXUpload5.deleteConfirm)){
					return false;
				}
				var deleteQueue = [];
				$.each(selectObj, function(){
					deleteQueue.push(this.id);
				});
				this.ccDelete(deleteQueue, 0);
				myUpload.custom.axDeleteQueue = deleteQueue.length; // NuriCms: delete 큐 생성
				deleteQueue = null;
			}else{
				toast.push({body:uploadSettingObj[editorSequence].lang.msg_file_cart_is_null, type:'Warning'}); // NuriCms: 삭제 대상이 없으면 메시지를 출력
			}
		}
	}
});

 // NuriCms: 설정용 객체
var myUpload = new AXUpload5();

var fnObj = {
	pageStart: function(cfg){
		fnObj.upload.init(cfg);
	},
	upload: {
		init: function(cfg){
			var seq = cfg.editorSequence;
		
			if(!is_def(seq)) return;

			cfg = $.extend({
				url : request_uri+'index.php',
				sessionName : "PHPSESSID"
			}, cfg);
		
			uploadSettingObj[seq] = cfg; // editor 설정정보 저장

			// 파일 박스안의 우클릭, 드래그 방지
			$("#uploadQueueBox").bind("contextmenu", function(event){event.preventDefault();});
			$("#uploadQueueBox").bind("selectstart", function(event){event.preventDefault();});
			$("#uploadQueueBox").bind("dragstart", function(event){event.preventDefault();});
			$("#uploadQueueBox").css('MozUserSelect','none');
			$("#uploadQueueBox").mousedown(function(){return false;});

			// NuriCms : XE는 결과값이 ok가 아닌 success
			AXConfig.AXReq.okCode = "success";
			// NuriCMs : 메시지 선언
			AXConfig.AXUpload5 = {
				buttonTxt:cfg.lang.uploadButtonTitle,
				deleteConfirm:cfg.lang.confirm_delete,
				uploadSelectTxt:cfg.uploadSelectTxt,
				dropZoneTxt:cfg.dropZoneTxt
			}
			AXConfig.AXProgress.cancelMsg = cfg.cancelMsg;

			myUpload.setConfig({
				targetID:"AXUpload5",
				targetButtonClass:"Blue",
				uploadFileName:"Filedata",
				file_types:"*.*",
				dropBoxID:"uploadQueueBox",
				queueBoxID:"uploadQueueBox",
				// html 5를 지원하지 않는 브라우저를 위한 swf upload 설정 원치 않는 경우엔 선언 하지 않아도 됩니다. ------- s
				flash_url : request_uri+"modules/editor/skins/xpresseditor_axisj/_AXJ/lib/swfupload.swf",
				flash9_url : request_uri+"modules/editor/skins/xpresseditor_axisj/_AXJ/lib/swfupload_fp9.swf",
				// --------- e
				onClickUploadedItem: function(){ // 업로드된 목록을 클릭했을 때.
					myUpload.custom.insertUploadedFile(cfg.editorSequence, this);
				},
				uploadMaxFileSize:cfg.uploadMaxFileSize, // 업로드될 개별 파일 사이즈 (클라이언트에서 제한하는 사이즈 이지 서버에서 설정되는 값이 아닙니다.)
				uploadMaxFileCount:0, // 업로드될 파일갯수 제한 0 은 무제한
				uploadUrl:cfg.url,
				uploadPars:{
					PHPSESSID : getCookie(cfg.sessionName),
					editor_sequence : cfg.editorSequence,
					mid : current_mid,
					act : "procFileUpload",
					upload_target_srl : editorRelKeys[cfg.editorSequence].primary.value,
					xe_json_callback : "xe_json_callback", // NuriCms: 결과를 json으로 받는다
					uploaded_count : true, // 등록된 파일 수를 받는다
					width : 70, // 이미지파일의 사이즈 정보를 받는다
//					file_list : true // 등록된 파일 목록과 설정정보를 받는다
				},
				deleteUrl:cfg.url,
				deletePars:{
					editor_sequence : cfg.editorSequence,
					module : "file",
					act : "procFileDelete",
					upload_target_srl : editorRelKeys[cfg.editorSequence].primary.value,
					xe_json_callback : "xe_json_callback", // NuriCms: 결과를 json으로 받는다
					uploaded_count : true, // 등록된 파일 수를 받는다
//					file_list : true // 등록된 파일 목록과 설정정보를 받는다
				},
				fileKeys:{ // 서버에서 리턴하는 json key 정의 (id는 예약어 사용할 수 없음)
					name:"source_filename",
					fileSize:"file_size",
					download_url:"download_url",
					uploaded_filename:"uploaded_filename",
					file_srl:"file_srl",
					thumbPath:"thumbnail_src",
					uploaded_count:"uploaded_count",
//					files:"files",
					editor_sequence:"editor_sequence",
					upload_status:"upload_status",
					left_size:"left_size",
				},
				onUpload: function(uploadedItem){ // 업로드 완료시 현재 등록파일의 총용량을 계산해서 화면에 반영
					cfg.insertedFiles = uploadedItem.uploaded_count;
					$('#'+cfg.uploaderStatusID+' .attach_size').html(filesize(cfg.uploadMaxFileSize - cfg.uploadLeftFileSize));
				},
				onComplete: function(data, ee){
					$("#uploadCancelBtn").get(0).disabled = true; // 전송중지 버튼 제어

					if(cfg.insertedFiles != myUpload.uploadedList.length){ // NuriCms: 파일박스에 등록된 파일수와 서버에 등록된 수가 불일치 할 경우 리로딩
						fnObj.upload.getFileList(cfg, true); 
					}

					// NuriCms: 업로드, 삭제 등 프로세스가 정상종료될 경우 서버로부터 정보를 받아옴(임시저장 처리용)
					myUpload.custom.reloadFileList(uploadSettingObj[cfg.editorSequence]);
				},
				onStart: function(){
					//*-- NuriCms: 파일을 업로드 하기 전에 검사 ----------s

					$.each(myUpload.queue, function(i, obj){
						// 확장자 검사
						if(cfg.allowed_filetypes != "*.*" &&
							cfg.allowed_filetypes.indexOf(obj.file.name.substring(obj.file.name.lastIndexOf('.')+1,obj.file.name.length).toLowerCase()) < 0){
							myUpload.config.onError(cfg.lang.allowed_filetypes);
							myUpload.cancelUpload();
							return false;
						}

						// 파일사이즈 검사
						cfg.uploadLeftFileSize -= obj.file.size;
						if(cfg.uploadLeftFileSize < 0){
							myUpload.config.onError(cfg.lang.uploadFileSize);
							myUpload.cancelUpload();
							return false;
						}
					});
					//*-- NuriCms: 파일을 업로드 하기 전에 검사 ----------e

					$("#uploadCancelBtn").get(0).disabled = false; // 전송중지 버튼 제어
				},
				onDelete: function(deletedItem){ // 삭제 완료시 현재 등록파일의 총용량을 계산해서 화면에 반영
					cfg.uploadLeftFileSize = Number(cfg.uploadLeftFileSize) + Number(deletedItem.file.file_size); // NuriCms: 파일삭제시 허용 파일사이즈 재계산
					cfg.insertedFiles = deletedItem.res.uploaded_count;
					$('#'+cfg.uploaderStatusID+' .attach_size').html(filesize(cfg.uploadMaxFileSize - cfg.uploadLeftFileSize));
				},
				onError: function(errorType, extData){ // NuriCms: 각종 에러 메시지 처리
					if(errorType == "html5Support"){
						// 브라우저 접근당 1회만 알림을 발생
						if(AXUtil.getCookie('AXUpload5mode')) return false;
						AXUtil.setCookie('AXUpload5mode', 'SWFUpload');
						toast.push({body:cfg.lang.error_html5Support, type:'Caution'});
					}else if(errorType == "res_error"){
						toast.push({body:cfg.lang.res_error, type:'Caution'});
					}else if(errorType == "fileSize"){
						toast.push({body:sprintf(cfg.lang.error_fileSize, extData.name, extData.size.byte()), type:'Warning'});
					}else if(errorType == "fileCount"){
						toast.push({body:cfg.lang.error_fileCount, type:'Warning'});
					}else{
						toast.push({body:errorType, type:'Warning'});
					}
				}
			});

			// 서버에 저장된 파일 목록을 불러와 업로드된 목록에 추가 합니다. ----------------------------- s
			fnObj.upload.getFileList(cfg); // NuriCms: 서버로부터 정보를 받아옴
			// 서버에 저장된 파일 목록을 불러와 업로드된 목록에 추가 합니다. ----------------------------- e
		},
		getFileList: function(cfg, upload_status){  // NuriCms: 서버로부터 정보를 받아옴
			function setUploadedList(response_tags){
				// 첨부파일 허용 사이즈 재계산
				if(upload_status == true){
					cfg.uploadLeftFileSize = response_tags.left_size;
					$('#'+cfg.uploaderStatusID+' .attach_size').html(filesize(cfg.uploadMaxFileSize - cfg.uploadLeftFileSize));
				}

				// 파일박스안에 내용을 초기화
				myUpload.uploadedList = [];
				$('#'+myUpload.config.queueBoxID).find('.AXUploadItem').remove();

				if(response_tags.files == undefined) {
					return false;
				}else if(!response_tags.files.item.length){
					response_tags.files.item[0] = response_tags.files.item;
				}

				// NuriCms: AXUpload5 element ID insert
				var res = [];
				$.each(response_tags.files.item, function(i, file){
					file.id = 'AX'+AXUtil.timekey()+'_AX_'+(response_tags.files.item.length - i-1);
					res[i] = file;
				});

				myUpload.setUploadedList(res);
			}

			var params = {
					mid : current_mid,
					module : 'file',
					act : 'getFileList',
					editor_sequence   : cfg.editorSequence,
					width : 70
				};

			exec_xml(
				'file',
				'getFileList',
				params,
				setUploadedList,
				'error,message,files,upload_status,upload_target_srl,editor_sequence,left_size'.split(',')
			);
		},
		changeOption: function(thumbvar){
			// 업로드 갯수 등 업로드 관련 옵션을 동적으로 변경 할 수 있습니다. 
			myUpload.changeConfig({
				fileKeys:{
					name:"source_filename",
					fileSize:"file_size",
					download_url:"download_url",
					uploaded_filename:"uploaded_filename",
					thumbPath:"thumbnail_src",
					uploaded_count:"uploaded_count",
//					files:"files",
					editor_sequence:"editor_sequence",
					upload_status:"upload_status",
					left_size:"left_size"
				}
			});	
			
		}
	}
};

// AXUpload5 실행
window.editorUploadInit = fnObj.pageStart;

$(function(){
	try { document.execCommand('BackgroundImageCache',false,true); } catch(e) { }
});


// NuriCms: sprintf(), filesize()를 할 수 있도록 함수 추가함

/*!{id:"uupaa.js",ver:0.7,license:"MIT",author:"uupaa.js@gmail.com"}*/
window.sprintf || (function() {
var _BITS = { i: 0x8011, d: 0x8011, u: 0x8021, o: 0x8161, x: 0x8261,
			X: 0x9261, f: 0x92, c: 0x2800, s: 0x84 },
	_PARSE = /%(?:(\d+)\$)?(#|0)?(\d+)?(?:\.(\d+))?(l)?([%iduoxXfcs])/g;

window.sprintf = _sprintf;

function _sprintf(format) {
	function _fmt(m, argidx, flag, width, prec, size, types) {
		if (types === "%") { return "%"; }
		var v = "", w = _BITS[types], overflow, pad;

		idx = argidx ? parseInt(argidx) : next++;

		w & 0x400 || (v = (av[idx] === void 0) ? "" : av[idx]);
		w & 3 && (v = (w & 1) ? parseInt(v) : parseFloat(v), v = isNaN(v) ? "": v);
		w & 4 && (v = ((types === "s" ? v : types) || "").toString());
		w & 0x20  && (v = (v >= 0) ? v : v % 0x100000000 + 0x100000000);
		w & 0x300 && (v = v.toString(w & 0x100 ? 8 : 16));
		w & 0x40  && (flag === "#") && (v = ((w & 0x100) ? "0" : "0x") + v);
		w & 0x80  && prec && (v = (w & 2) ? v.toFixed(prec) : v.slice(0, prec));
		w & 0x6000 && (overflow = (typeof v !== "number" || v < 0));
		w & 0x2000 && (v = overflow ? "" : String.fromCharCode(v));
		w & 0x8000 && (flag = (flag === "0") ? "" : flag);
		v = w & 0x1000 ? v.toString().toUpperCase() : v.toString();

		if (!(w & 0x800 || width === void 0 || v.length >= width)) {
		pad = Array(width - v.length + 1).join(!flag ? " " : flag === "#" ? " " : flag);
		v = ((w & 0x10 && flag === "0") && !v.indexOf("-"))
			? ("-" + pad + v.slice(1)) : (pad + v);
		}
		return v;
	}
	var next = 1, idx = 0, av = arguments;
	
	return format.replace(_PARSE, _fmt);
}

window.filesize = _filesize;

function _filesize(size)
{
	if(!size)
	{
		return '0Byte';
	}

	if(size === 1)
	{
		return '1Byte';
	}

	if(size < 1024)
	{
		return size+'Bytes';
	}

	if(size >= 1024 && size < 1024 * 1024)
	{
		return sprintf("%0.1fKB", size / 1024);
	}

	return sprintf("%0.2fMB", size / (1024 * 1024));
}

})();