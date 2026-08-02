/**
 * @license Copyright (c) 2003-2018, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * 댓글용 간단한 CKEditor 설정
 */
CKEDITOR.editorConfig = function( config ) {
    // 기본 언어 설정
    config.language = 'ko';

    // 높이 설정 (댓글용으로 작게)
    config.height = 120;

    // 필수 플러그인 설정 (이미지 업로드 포함)
    config.extraPlugins = 'emoji,uploadwidget,uploadimage';

    // 이모지 설정
    config.emoji_emojiListUrl = '/assets/editors/ckeditor4/plugins/emoji/sir_emoji.json';
    config.emoji_minChars = 1;

    // 간단한 툴바 설정 (댓글용) - 이모지를 맨 앞으로
    config.toolbar = [
        { name: 'emoji', items: [ 'EmojiPanel' ] },
        { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] },
        { name: 'insert', items: [ 'Image' ] },
        { name: 'colors', items: [ 'TextColor' ] },
        { name: 'links', items: [ 'Link', 'Unlink' ] },
        { name: 'tools', items: [ 'Maximize' ] }
    ];

    // 크기 조절 비활성화
    config.resize_enabled = false;

    // 엔터키 설정
    config.enterMode = CKEDITOR.ENTER_BR;
    config.shiftEnterMode = CKEDITOR.ENTER_P;

    // 붙여넣기 설정
    config.forcePasteAsPlainText = false;
    config.pasteFromWordPromptCleanup = true;

    // 콘텐츠 CSS
    config.contentsCss = '/assets/editors/ckeditor4/contents.css';

    // 상태바 숨기기
    config.removePlugins = 'elementspath';

    // 모든 콘텐츠 허용
    config.allowedContent = true;
};

/**
 * 댓글용 CKEditor 초기화 헬퍼 함수
 * @param {string} elementId - 에디터를 적용할 element의 ID
 * @param {Object} customConfig - 추가 설정 (선택사항)
 * @param {string} csrfToken - CSRF 토큰 (인증된 사용자만)
 * @returns {CKEDITOR.editor} - 생성된 에디터 인스턴스
 */
window.initializeCommentEditor = function(elementId, customConfig, csrfToken) {
    // 요소 확인
    var element = document.getElementById(elementId);
    if (!element) {
        // console.error('Comment Editor: Element not found:', elementId);
        return null;
    }

    // 이미 초기화된 경우 제거
    if (CKEDITOR.instances[elementId]) {
        CKEDITOR.instances[elementId].destroy(true);
    }

    // 인증 여부에 따른 툴바 설정
    var toolbarItems = [
        { name: 'emoji', items: [ 'EmojiPanel' ] },
        { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] }
    ];

    // 인증된 사용자에게만 이미지 업로드 버튼 추가
    if (csrfToken) {
        toolbarItems.push({ name: 'insert', items: [ 'Image' ] });
    }

    toolbarItems.push(
        { name: 'colors', items: [ 'TextColor' ] },
        { name: 'links', items: [ 'Link', 'Unlink' ] },
        { name: 'tools', items: [ 'Maximize' ] }
    );

    // placeholder 텍스트 가져오기 (textarea의 placeholder 속성에서)
    var placeholderText = '';
    if (element && element.getAttribute('placeholder')) {
        placeholderText = element.getAttribute('placeholder');
    } else if (customConfig && customConfig.placeholder) {
        placeholderText = customConfig.placeholder;
    }

    // 댓글용 기본 설정
    var config = {
        height: customConfig?.height || 120,
        language: 'ko',
        extraPlugins: csrfToken ? 'emoji,uploadwidget,uploadimage,editorplaceholder,autogrow' : 'emoji,editorplaceholder,autogrow',
        emoji_emojiListUrl: '/assets/editors/ckeditor4/plugins/emoji/sir_emoji.json',
        emoji_minChars: 1,
        toolbar: toolbarItems,
        resize_enabled: false,
        enterMode: CKEDITOR.ENTER_BR,
        shiftEnterMode: CKEDITOR.ENTER_P,
        forcePasteAsPlainText: false,
        pasteFromWordPromptCleanup: true,
        contentsCss: '/assets/editors/ckeditor4/contents.css',
        removePlugins: 'elementspath',
        allowedContent: true,
        // autogrow 설정
        autoGrow_minHeight: customConfig?.height || 120,
        autoGrow_maxHeight: 400,
        autoGrow_bottomSpace: 10,
        autoGrow_onStartup: true
    };

    // placeholder 설정
    if (placeholderText) {
        config.editorplaceholder = placeholderText;
    }

    // 사용자 정의 설정 병합 (업로드 URL 구성 전에 먼저 실행)
    // 단, 업로드 URL 관련 파라미터는 제외
    if (customConfig) {
        for (var key in customConfig) {
            if (customConfig.hasOwnProperty(key) &&
                key !== 'bo_table' &&
                key !== 'wr_id' &&
                key !== 'editor_form_name' &&
                key !== 'editor_id' &&
                key !== 'editor_uri') {
                config[key] = customConfig[key];
            }
        }
    }

    // 인증된 사용자에게 이미지 업로드 기능 추가
    if (csrfToken) {
        // 기본 업로드 URL
        var uploadUrlBase = (typeof g5_url !== 'undefined') ? g5_url + '/api/editor/ckeditor4/upload' : '/api/editor/ckeditor4/upload';
        var uploadUrl = uploadUrlBase + '?is_cm=1&_token=' + csrfToken;

        // 게시판 정보 추가 (customConfig에서 제공되는 경우)
        if (customConfig) {

            if (customConfig.bo_table) {
                uploadUrl += '&bo_table=' + encodeURIComponent(customConfig.bo_table);
            }
            if (customConfig.wr_id) {
                uploadUrl += '&wr_id=' + encodeURIComponent(customConfig.wr_id);
            }
            if (customConfig.comment_id) {
                uploadUrl += '&comment_id=' + encodeURIComponent(customConfig.comment_id);
            }
            if (customConfig.editor_form_name) {
                uploadUrl += '&editor_form_name=' + encodeURIComponent(customConfig.editor_form_name);
            }
            if (customConfig.editor_id) {
                uploadUrl += '&editor_id=' + encodeURIComponent(customConfig.editor_id);
            }
            if (customConfig.editor_uri) {
                uploadUrl += '&editor_uri=' + encodeURIComponent(customConfig.editor_uri);
            }
        }

        config.filebrowserUploadUrl = uploadUrl;
        config.filebrowserImageUploadUrl = uploadUrl;
        config.imageUploadUrl = uploadUrl + '&responseType=json';
    }

    // 에디터 생성
    var editor = CKEDITOR.replace(elementId, config);

    // 다크 모드 지원
    editor.on('instanceReady', function(evt) {
        if (window.applyCKEditorDarkMode) {
            window.applyCKEditorDarkMode(evt.editor);
        }
    });

    // 폼 제출 시 자동 동기화
    var form = element.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (CKEDITOR.instances[elementId]) {
                CKEDITOR.instances[elementId].updateElement();
            }
        });
    }

    return editor;
};
