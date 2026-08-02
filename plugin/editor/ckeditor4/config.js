/**
 * @license Copyright (c) 2003-2018, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

// ckeditor4 자산 베이스 — editor.lib.php 가 내보내는 g5_editor_url 을 쓴다
window.G5_CKEDITOR4_URL = (typeof g5_editor_url !== 'undefined') ? g5_editor_url : '/plugin/editor/ckeditor4';
// 업로드는 plugin 안의 upload.php 직통 (g5se 는 /api/... 짧은주소 라우트였다)
window.G5_CKEDITOR4_UPLOAD_URL = window.G5_CKEDITOR4_URL + '/upload.php';

// g5se: 페이지(parent) 의 다크모드 감지 — html.dark 또는 [data-theme=dark]
window.G5_CKEDITOR4_IS_DARK = function () {
    var de = document.documentElement;
    return de.classList.contains('dark') || de.getAttribute('data-theme') === 'dark';
};

CKEDITOR.editorConfig = function( config ) {
    // 기본 언어 설정
    config.language = 'ko';

    // 높이 설정
    config.height = 400;

    // 필수 플러그인 설정
    config.extraPlugins = 'emoji,uploadwidget,uploadimage';

    // 다크모드: 툴바/크롬은 moono-dark 스킨, 본문 iframe 도 darkmode.css 추가 로드
    if (window.G5_CKEDITOR4_IS_DARK && window.G5_CKEDITOR4_IS_DARK()) {
        config.skin = 'moono-dark';
    }

    // 이모지 설정
    config.emoji_emojiListUrl = window.G5_CKEDITOR4_URL + '/plugins/emoji/sir_emoji.json';
    config.emoji_minChars = 1;

    // 툴바 그룹 설정
    config.toolbarGroups = [
        { name: '1', groups: [ 'emoji', 'styles', 'align', 'basicstyles', 'cleanup' ] },
        { name: '2', groups: [ 'insertImg', 'insert', 'colors', 'list', 'blocks', 'links', 'mode', 'tools', 'about' ] }
    ];

    // 크기 조절 활성화
    config.resize_enabled = true;

    // 엔터키 설정
    config.enterMode = CKEDITOR.ENTER_BR;
    config.shiftEnterMode = CKEDITOR.ENTER_P;

    // 붙여넣기 설정
    config.forcePasteAsPlainText = false;
    config.pasteFromWordPromptCleanup = true;

    // 콘텐츠 CSS — 다크모드면 darkmode.css 추가 (.cke_editable 톤 적용)
    config.contentsCss = (window.G5_CKEDITOR4_IS_DARK && window.G5_CKEDITOR4_IS_DARK())
        ? [ window.G5_CKEDITOR4_URL + '/contents.css', window.G5_CKEDITOR4_URL + '/darkmode.css' ]
        : window.G5_CKEDITOR4_URL + '/contents.css';

    // g5se: 이미지 / 파일 업로드 — plugin 안의 upload.php 사용 (gnuboard 표준 경로).
    // class 자동치환(.ckeditor) 으로 만들어진 인스턴스 전체에 적용.
    config.filebrowserUploadUrl  = window.G5_CKEDITOR4_UPLOAD_URL;
    config.filebrowserImageUploadUrl = window.G5_CKEDITOR4_UPLOAD_URL + '?type=image';
    config.imageUploadUrl        = window.G5_CKEDITOR4_UPLOAD_URL + '?responseType=json';

    // 폰트 설정
    config.font_names = '굴림/Gulim;돋움/Dotum;바탕/Batang;궁서/Gungsuh;맑은 고딕/Malgun Gothic;' +
                       'Arial/Arial;Comic Sans MS/Comic Sans MS;Courier New/Courier New;' +
                       'Georgia/Georgia;Tahoma/Tahoma;Times New Roman/Times New Roman;Verdana/Verdana';

    // 폰트 사이즈
    config.fontSize_sizes = '8/8px;9/9px;10/10px;11/11px;12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px';

    // 다이얼로그 탭 제거
    config.removeDialogTabs = 'image:advanced;link:advanced';

	// iOS만 적용
	// if(/iPhone|iPad|iPod/i.test(navigator.userAgent) ) {
		// 이 문제가 이번 버전에서 해결이 되었는지 모르겠다.
		// 한글 입력 관련 줄바꿈 과정에서 문제발생하여 적용
		// config.removePlugins = 'enterkey';
	// }
	
    // 모든 콘텐츠 허용
    config.allowedContent = true;
};

// g5se: 클래스 자동 치환(.ckeditor)으로 만들어지는 인스턴스에도 다크모드 적용.
// initializeCKEditor() 헬퍼를 거치지 않으므로 글로벌 instanceReady 핸들러 한번 더 바인딩.
CKEDITOR.on('instanceReady', function (evt) {
    if (typeof window.applyCKEditorDarkMode === 'function') {
        try { window.applyCKEditorDarkMode(evt.editor); } catch (e) {}
    }

    // 드롭다운 패널이 열릴 때마다 그 안쪽에 다크 스타일을 넣는다.
    evt.editor.on('panelShow', function () {
        try { window.applyCKEditorPanelDarkMode(); } catch (e) {}
    });

    // (notifications_area 를 .cke 안으로 옮기는 로직은 panel 위치 계산을 깨서
    //  emoji / font 팝업이 페이지 하단에 뜨는 문제를 만들었음 — 제거.
    //  알림 toast 위치는 CKEditor 기본 그대로 두고 admin.css 의 카드 스타일만 적용.)
});

/**
 * 드롭다운 패널(이모지·글꼴·크기·색상) 안쪽 다크모드.
 *
 * 패널 내용은 iframe.cke_panel_frame 안에 그려져 페이지 CSS 도 darkmode.page.css 도
 * 닿지 않는다. contentsCss 로 들어가는 darkmode.css 는 글꼴 패널까지는 실리지만
 * 이모지 패널은 plugins/emoji/plugin.js 가 config.contentsCss 대신 기본 contents.css 를
 * 직접 붙이는 탓에 아예 실리지 않는다. 그래서 열릴 때마다 직접 넣는다.
 *
 * 매번 현재 테마를 다시 읽으므로 토글에도 따라온다. 같은 iframe 에 두 번 넣지 않는다.
 */
window.applyCKEditorPanelDarkMode = function () {
    var isDark = window.G5_CKEDITOR4_IS_DARK && window.G5_CKEDITOR4_IS_DARK();
    var frames = document.querySelectorAll('iframe.cke_panel_frame');

    for (var i = 0; i < frames.length; i++) {
        var doc;
        try {
            doc = frames[i].contentDocument || frames[i].contentWindow.document;
        } catch (e) {
            continue;   // 다른 출처면 건드릴 수 없다
        }
        if (!doc || !doc.head) continue;

        var old = doc.getElementById('ckeditor-dark-panel');
        if (old) old.parentNode.removeChild(old);
        if (!isDark) continue;

        var style = doc.createElement('style');
        style.id = 'ckeditor-dark-panel';
        style.textContent = window.G5_CKEDITOR4_PANEL_DARK_CSS;
        doc.head.appendChild(style);
    }
};

// 색은 사이트 팔레트(style.css 의 :root[data-theme="dark"])와 같은 값을 쓴다.
// iframe 안이라 CSS 변수가 상속되지 않아 값을 그대로 적는다.
window.G5_CKEDITOR4_PANEL_DARK_CSS = [
    // iframe 은 부모 문서의 color-scheme 을 물려받지 않는다. 이걸 빼면 스크롤바가 흰 채로 남는다
    'html { color-scheme: dark; }',
    'html, body { background: #16202E !important; color: #E6EDF5 !important; }',

    /* 글꼴·크기·서식 등 목록형 패널 */
    '.cke_panel_list, .cke_panel_listItem a { color: #E6EDF5 !important; }',
    '.cke_panel_listItem a { background-color: transparent !important; }',
    '.cke_panel_listItem a:hover, .cke_panel_listItem a:focus, .cke_panel_listItem a:active,',
    '.cke_panel_listItem.cke_selected a {',
    '  background-color: #1B2837 !important; border-color: #23303F !important; color: #E6EDF5 !important; }',
    '.cke_panel_grouptitle {',
    '  background: #1E2A3A !important; background-image: none !important;',
    '  color: #E6EDF5 !important; border-color: #23303F !important;',
    '  box-shadow: none !important; text-shadow: none !important; }',

    /* 이모지 패널 */
    '.cke_emoji-inner_panel > h2 { color: #E6EDF5 !important; }',
    '.cke_emoji-inner_panel > nav { border-bottom-color: #23303F !important; }',
    '.cke_emoji-inner_panel > nav li a { color: #8B9BB0 !important; }',
    '.cke_emoji-panel_block a:hover, .cke_emoji-panel_block a:focus {',
    '  background-color: #1B2837 !important; }',
    '.cke_emoji-search { border-color: #23303F !important; }',
    '.cke_emoji-search input { background: #0F1621 !important; color: #E6EDF5 !important; }',
    '.cke_emoji-status_bar { border-top-color: #23303F !important; }',
    'p.cke_emoji-status_full_name { color: #8B9BB0 !important; }',
    '.cke_emoji-inner_panel a:focus, .cke_emoji-inner_panel input:focus {',
    '  outline-color: #4A90FF !important; }',

    /* 색상 고르기 패널 — 칸 색은 그대로 두고 틀만 맞춘다 */
    '.cke_colorbox { border-color: #23303F !important; }',
    '.cke_colorauto, .cke_colormore {',
    '  background-color: #1E2A3A !important; color: #E6EDF5 !important; border-color: #23303F !important; }'
].join('\n');

/**
 * CKEditor 초기화 헬퍼 함수
 * @param {string} elementId - 에디터를 적용할 element의 ID
 * @param {Object} customConfig - 추가 설정 (선택사항)
 * @param {boolean} isAuthenticated - 인증 여부 (파일 업로드 권한)
 * @param {string} csrfToken - CSRF 토큰 (인증 사용자용)
 * @param {Object} uploadParams - 업로드 URL에 추가할 파라미터 (bo_table, wr_id, editor_form_name, editor_id, editor_uri)
 * @returns {CKEDITOR.editor} - 생성된 에디터 인스턴스
 */
window.initializeCKEditor = function(elementId, customConfig, isAuthenticated, csrfToken, uploadParams) {
    // 요소 확인
    var element = document.getElementById(elementId);
    if (!element) {
        console.error('CKEditor: Element not found:', elementId);
        return null;
    }

    // 이미 초기화된 경우 제거
    if (CKEDITOR.instances[elementId]) {
        CKEDITOR.instances[elementId].destroy(true);
    }

    // 기본 설정 복사
    var config = {
        height: customConfig?.height || 400,
        language: customConfig?.language || 'ko'
    };

    // 인증된 사용자 설정
    if (isAuthenticated && csrfToken) {
        // 업로드 URL 기본 파라미터
        var uploadUrlParams = '_token=' + encodeURIComponent(csrfToken);
        
        // 추가 파라미터가 있으면 추가
        if (uploadParams) {
            if (uploadParams.bo_table) {
                uploadUrlParams += '&bo_table=' + encodeURIComponent(uploadParams.bo_table);
            }
            if (uploadParams.wr_id !== undefined && uploadParams.wr_id !== null) {
                uploadUrlParams += '&wr_id=' + encodeURIComponent(uploadParams.wr_id);
            }
            if (uploadParams.editor_form_name) {
                uploadUrlParams += '&editor_form_name=' + encodeURIComponent(uploadParams.editor_form_name);
            }
            if (uploadParams.editor_id) {
                uploadUrlParams += '&editor_id=' + encodeURIComponent(uploadParams.editor_id);
            }
            if (uploadParams.editor_uri) {
                uploadUrlParams += '&editor_uri=' + encodeURIComponent(uploadParams.editor_uri);
            }
        }
        
        config.filebrowserUploadUrl = window.G5_CKEDITOR4_UPLOAD_URL + '?' + uploadUrlParams;
        config.filebrowserImageUploadUrl = window.G5_CKEDITOR4_UPLOAD_URL + '?' + uploadUrlParams;
        config.imageUploadUrl = window.G5_CKEDITOR4_UPLOAD_URL + '?' + uploadUrlParams + '&responseType=json';
        config.extraPlugins = 'emoji,uploadwidget,uploadimage';
    } else {
        config.extraPlugins = 'emoji';
    }

    // 사용자 정의 설정 병합
    if (customConfig) {
        for (var key in customConfig) {
            if (customConfig.hasOwnProperty(key) && key !== 'height' && key !== 'language') {
                config[key] = customConfig[key];
            }
        }
    }

    // 에디터 생성
    var editor = CKEDITOR.replace(elementId, config);

    // 다크 모드 지원
    editor.on('instanceReady', function(evt) {
        window.applyCKEditorDarkMode(evt.editor);
    });

    // 모드 변경 시 다크모드 재적용 (소스보기 <-> WYSIWYG)
    editor.on('mode', function(evt) {
        if (evt.editor.mode === 'wysiwyg') {
            // WYSIWYG 모드로 전환 시 약간의 지연 후 다크모드 재적용
            setTimeout(function() {
                window.applyCKEditorDarkMode(evt.editor);
            }, 100);
        }
    });

    // 폼 제출 시 자동 동기화
    var form = element.closest('form');
    if (form) {
        // HTML 체크박스 자동 처리
        var htmlCheckbox = form.querySelector('input[name="html"]');
        if (htmlCheckbox) {
            htmlCheckbox.checked = true;
            var htmlLabel = htmlCheckbox.closest('label');
            if (htmlLabel) {
                htmlLabel.style.display = 'none';
            }
        }

        // 폼 제출 이벤트
        form.addEventListener('submit', function(e) {
            if (CKEDITOR.instances[elementId]) {
                CKEDITOR.instances[elementId].updateElement();
            }
        });
    }

    return editor;
};

/**
 * 다크 모드 적용 함수
 * @param {CKEDITOR.editor} editorInstance - CKEditor 인스턴스
 */
window.applyCKEditorDarkMode = function(editorInstance) {
    var iframe = document.getElementById(editorInstance.id + '_contents');
    if (iframe) {
        iframe = iframe.querySelector('iframe');
    }

    if (!iframe) return;

    function applyDarkMode(isDark) {
        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        var iframeBody = iframeDoc.body;

        // 기존 스타일 제거
        var existingStyle = iframeDoc.getElementById('ckeditor-dark-style');
        if (existingStyle) {
            existingStyle.remove();
        }

        if (isDark) {
            // 다크 모드 스타일 적용
            iframeBody.style.backgroundColor = '#111827';
            iframeBody.style.color = '#f3f4f6';

            var darkStyle = iframeDoc.createElement('style');
            darkStyle.id = 'ckeditor-dark-style';
            darkStyle.textContent = `
                /* 없으면 편집영역 스크롤바가 흰 채로 남는다 — iframe 은 부모의 color-scheme 을 안 받는다 */
                html { color-scheme: dark; }
                body {
                    background-color: #111827 !important;
                    color: #f3f4f6 !important;
                }
                p, div, span, li {
                    color: #f3f4f6 !important;
                }
                a { color: #60a5fa !important; }
                blockquote {
                    background-color: #1f2937 !important;
                    border-left-color: #4b5563 !important;
                    color: #d1d5db !important;
                }
                pre {
                    background-color: #1f2937 !important;
                    color: #d1d5db !important;
                }
                code {
                    background-color: #1f2937 !important;
                    color: #fbbf24 !important;
                }
                table {
                    border-color: #4b5563 !important;
                }
                td, th {
                    border-color: #4b5563 !important;
                    color: #f3f4f6 !important;
                }
                hr {
                    border-color: #4b5563 !important;
                }
                ::selection {
                    background-color: #3b82f6 !important;
                    color: #ffffff !important;
                }
            `;
            iframeDoc.head.appendChild(darkStyle);
        } else {
            // 라이트 모드 복원
            iframeBody.style.backgroundColor = '#ffffff';
            iframeBody.style.color = '#000000';
        }
    }

    function isParentDark() {
        var de = document.documentElement;
        return de.classList.contains('dark') || de.getAttribute('data-theme') === 'dark';
    }

    // 초기 적용
    applyDarkMode(isParentDark());

    // 다크 모드 토글 감지 — class / data-theme 둘 다 감시
    var observer = new MutationObserver(function() {
        applyDarkMode(isParentDark());
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-theme']
    });
};
