<div id="editorjs" style="max-width: 700px; margin: 0 auto;"></div>

<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>

<script>
const editor = new EditorJS({
  holder: 'editorjs',
  placeholder: '記事を書いてみましょう...',
  tools: {
    header: {
      class: Header,
      config: { placeholder: '見出しを入力', levels: [2, 3], defaultLevel: 2 }
    },
    list: {
      class: EditorjsList,
      inlineToolbar: true
    },
    quote: {
      class: Quote,
      inlineToolbar: true
    },
    image: {
      class: ImageTool,
      config: {
        endpoints: {},
        // 保存API未実装のため、今はURL指定のみ有効にしておく
        uploader: {
          uploadByUrl(url) {
            return Promise.resolve({
              success: 1,
              file: { url }
            });
          }
        }
      }
    }
  }
});
</script>