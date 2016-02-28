(function () {
    CKEDITOR.dialog.add('youtube', function (editor) {
        return {
            title: editor.lang.youtube.title,
            minWidth: CKEDITOR.env.ie && CKEDITOR.env.quirks ? 368 : 350,
            minHeight: 240,
            onShow: function () {
        		CKEDITOR.document.getById('video_id').setValue('');
        		CKEDITOR.document.getById('video_width').setValue('');
        		CKEDITOR.document.getById('video_height').setValue('');
        		CKEDITOR.document.getById('video_style').setValue('');
            },
            onOk: function () {
            	var value = CKEDITOR.document.getById('video_id').getValue();
            	if(!value) return;
            	width = CKEDITOR.document.getById('video_width').getValue();
            	if(!width) { width = 480; }
            	height = CKEDITOR.document.getById('video_height').getValue();
            	if(!height) { height = 390; }
            	style = CKEDITOR.document.getById('video_style').getValue();
            	if(style) { style = 'style="'+style+'"'; }
                var text = '<iframe ' + style + ' title="YouTube video player" class="youtube-player" type="text/html" width="' + width + '" height="' + height + '" src="http://www.youtube.com/embed/' + value + '?rel=0" frameborder="0"></iframe>';
                this.getParentEditor().insertHtml(text);
            },
            contents: [{
                label: editor.lang.common.generalTab,
                id: 'general',
                elements: [{
                    type: 'html',
                    id: 'pasteMsg',
                    html: '<div style="white-space:normal;width:500px;">' + editor.lang.youtube.pasteMsg + '</div>'
                }, {
                    type: 'html',
                    id: 'content',
                    style: 'width:340px;height:90px',
                    html: (editor.lang.youtube.id +' <input id="video_id" name="id" size="25" style="' + 'border:1px solid black;' + 'background:white"><br />' + editor.lang.youtube.width + ' <input id="video_width" name="width" size="25" style="' + 'border:1px solid black;' + 'background:white"><br />' + editor.lang.youtube.height + ' <input id="video_height" name="height" size="25" style="' + 'border:1px solid black;' + 'background:white"><br />' + editor.lang.youtube.style + ' <input id="video_style" name="style" size="25" style="' + 'border:1px solid black;' + 'background:white">'),
                    focus: function () {
                        this.getElement().focus()
                    }
                }]
            }]
        }
    })
})();