/**
 * Simple Emoji Plugin - Panel button only, no autocomplete
 */

(function() {
    'use strict';

    CKEDITOR.plugins.add('simpleemoji', {
        requires: 'panelbutton',
        lang: 'ko,en',
        icons: 'emojipanel',
        hidpi: true,

        init: function(editor) {
            var self = this;
            var lang = editor.lang.simpleemoji || { button: '이모지' };

            // 이모지 데이터 저장
            editor._.simpleEmoji = {
                list: [],
                groups: []
            };

            // 이모지 데이터 로드
            var emojiUrl = editor.config.emoji_emojiListUrl || '/assets/editors/ckeditor4/plugins/simpleemoji/sir_emoji.json';
            
            // Ajax로 이모지 데이터 로드
            if (window.jQuery) {
                jQuery.ajax({
                    url: emojiUrl,
                    dataType: 'json',
                    success: function(data) {
                        editor._.simpleEmoji.list = data;
                        
                        // 그룹별로 정리
                        var groups = {};
                        data.forEach(function(item) {
                            if (!groups[item.group]) {
                                groups[item.group] = [];
                            }
                            groups[item.group].push(item);
                        });
                        editor._.simpleEmoji.groups = groups;
                    },
                    error: function() {
                        console.error('Failed to load emoji data');
                    }
                });
            }

            // 패널 버튼 추가 - EmojiPanel로 이름 통일
            editor.ui.add('EmojiPanel', CKEDITOR.UI_PANELBUTTON, {
                label: lang.button || '이모지',
                title: lang.button || '이모지',
                modes: { wysiwyg: 1 },
                editorFocus: 0,
                toolbar: 'insert',
                
                panel: {
                    css: CKEDITOR.skin.getPath('editor'),
                    attributes: { role: 'listbox', 'aria-label': lang.button }
                },
                
                onBlock: function(panel, block) {
                    block.autoSize = true;
                    block.element.addClass('cke_emojipanel');
                    
                    var groups = editor._.simpleEmoji.groups;
                    var html = '<div class="cke_emoji_panel" style="width:400px;max-height:300px;overflow-y:auto;padding:10px;">';
                    
                    for (var groupName in groups) {
                        html += '<div class="cke_emoji_group">';
                        html += '<h4 style="margin:10px 0 5px;padding:5px;background:#f0f0f0;font-size:12px;">' + groupName + '</h4>';
                        html += '<div class="cke_emoji_list" style="display:flex;flex-wrap:wrap;gap:5px;">';
                        
                        // g5se: emoji PNG base 통일 (config.js 의 G5_CKEDITOR4_URL 사용)
                        var _emojiBase = (window.G5_CKEDITOR4_URL || '/plugin/editor/ckeditor4') + '/plugins/emoji/img';
                        groups[groupName].forEach(function(emoji) {
                            var emojiHtml = '<img src="' + _emojiBase + '/' + emoji.symbol + '" class="sir-emoji ' + emoji.group + '">';
                            html += '<a href="javascript:void(0)" ';
                            html += 'class="cke_emoji_item" ';
                            html += 'data-emoji=\'' + emojiHtml.replace(/'/g, "\\'") + '\' ';
                            html += 'style="display:inline-block;padding:5px;cursor:pointer;border:1px solid transparent;" ';
                            html += 'title="' + emoji.name + '">';
                            html += '<img src="' + _emojiBase + '/' + emoji.symbol + '" style="width:30px;height:30px;object-fit:contain;">';
                            html += '</a>';
                        });
                        
                        html += '</div></div>';
                    }
                    
                    html += '</div>';
                    
                    block.element.setHtml(html);
                    
                    // 클릭 이벤트 처리
                    block.element.on('click', function(evt) {
                        var target = evt.data.getTarget();
                        var item = target.getAscendant('a', true);
                        
                        if (item && item.hasClass('cke_emoji_item')) {
                            var emojiHtml = item.getAttribute('data-emoji');
                            editor.insertHtml(emojiHtml);
                            CKEDITOR.dialog._.currentTop && CKEDITOR.dialog._.currentTop.hide();
                            panel.hide();
                            evt.data.preventDefault();
                        }
                    });
                }
            });
        }
    });

    // 언어 파일
    CKEDITOR.plugins.setLang('simpleemoji', 'ko', {
        button: '이모지'
    });

    CKEDITOR.plugins.setLang('simpleemoji', 'en', {
        button: 'Emoji'
    });
})();