<script src="pintura-iife.js"></script>
<script src="useEditorWithJQuery-iife.js"></script>

<div class='contianer'>
    <div class="editor" style="height: 80vh"></div>
    <img class="result" src="" alt="" />
</div>

<script>
    useEditorWithJQuery(jQuery, pintura);

    var editor = $(".editor").pinturaDefault({
        src: "image.jpeg"
    });
</script>