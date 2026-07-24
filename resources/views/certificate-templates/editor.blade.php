<style>
    #canvas-stage{min-height:280px;overflow:hidden}
    #canvas-wrap{position:relative;flex:none;overflow:visible}
    #canvas-wrap .canvas-container{position:absolute!important;left:0;top:0;transform-origin:top left}
</style>
<form id="template-form" method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">@csrf @if($method !== 'POST') @method($method) @endif
    <div class="grid gap-6 xl:grid-cols-[280px_1fr]"><div class="space-y-5"><div><label for="template-name" class="mb-2 block text-sm font-bold">اسم القالب</label><input id="template-name" name="name" value="{{ old('name', $template?->name) }}" required class="w-full rounded-2xl border border-slate-200 bg-sand px-4 py-3 outline-none focus:border-emerald-700"></div><div><label for="background" class="mb-2 block text-sm font-bold">صورة الخلفية {{ $template ? '(اختياري)' : '' }}</label><input id="background" name="background" type="file" accept="image/png,image/jpeg,image/webp" {{ $template ? '' : 'required' }} class="w-full rounded-2xl border border-slate-200 bg-sand p-3 text-sm"></div><div class="rounded-2xl bg-emerald-50 p-4 text-sm leading-7 text-emerald-900"><i class="bx bx-info-circle"></i> أضف عنصر اسم الطالب قبل الحفظ. يمكنك سحب العناصر مباشرة داخل التصميم.</div><div class="grid grid-cols-2 gap-2"><button type="button" id="add-text" class="rounded-xl bg-emerald-950 px-3 py-3 text-sm font-bold text-white">إضافة نص</button><button type="button" id="add-student" class="rounded-xl bg-gold px-3 py-3 text-sm font-bold text-emerald-950">اسم الطالب</button><button type="button" id="delete-object" class="col-span-2 rounded-xl bg-red-50 px-3 py-3 text-sm font-bold text-red-600">حذف العنصر المحدد</button></div><div class="space-y-3 rounded-2xl border border-slate-200 p-4"><label class="block text-xs font-bold text-slate-400">خصائص العنصر المحدد</label><input id="object-text" placeholder="محتوى النص" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"><div class="grid grid-cols-2 gap-2"><input id="object-size" type="number" min="8" max="180" value="32" class="rounded-xl border border-slate-200 px-3 py-2 text-sm"><input id="object-color" type="color" value="#062c26" class="h-10 w-full rounded-xl border border-slate-200"></div><select id="object-weight" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"><option value="400">عادي</option><option value="700">عريض</option></select></div></div><div id="canvas-stage" class="rounded-3xl bg-slate-100 p-4"><div id="canvas-wrap" class="mx-auto bg-white shadow-xl"><canvas id="certificate-canvas"></canvas></div></div></div>
    <input type="hidden" name="canvas_json" id="canvas-json"><input type="hidden" name="width" id="canvas-width"><input type="hidden" name="height" id="canvas-height"><div class="flex justify-end border-t border-slate-100 pt-5"><button class="rounded-2xl bg-emerald-950 px-7 py-3.5 font-bold text-white">حفظ القالب <i class="bx bx-save mr-1 text-gold"></i></button></div>
</form>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
const initialBackground=@json($backgroundUrl);const initialJson=@json($initialJson);let editorCanvas=new fabric.Canvas('certificate-canvas',{preserveObjectStacking:true});let backgroundImage=null;let imageReady=Boolean(initialBackground);const setDimensions=(width,height)=>{editorCanvas.setDimensions({width,height});document.getElementById('canvas-width').value=width;document.getElementById('canvas-height').value=height;};const setBackground=(url)=>fabric.Image.fromURL(url,(image)=>{backgroundImage=image;setDimensions(image.width,image.height);editorCanvas.setBackgroundImage(image,editorCanvas.renderAll.bind(editorCanvas));if(initialJson){editorCanvas.loadFromJSON(initialJson,()=>{editorCanvas.setBackgroundImage(backgroundImage,editorCanvas.renderAll.bind(editorCanvas));editorCanvas.renderAll();});}});if(initialBackground)setBackground(initialBackground);document.getElementById('background').addEventListener('change',(event)=>{const file=event.target.files[0];if(file)setBackground(URL.createObjectURL(file));});const addText=(text,data={})=>{const object=new fabric.Textbox(text,{left:editorCanvas.getWidth()/2-120,top:editorCanvas.getHeight()/2-25,width:240,fontFamily:'Amiri',fontSize:32,fill:'#062c26',fontWeight:'400',textAlign:'center',data});editorCanvas.add(object).setActiveObject(object);editorCanvas.renderAll();};document.getElementById('add-text').addEventListener('click',()=>addText('نص الشهادة'));document.getElementById('add-student').addEventListener('click',()=>{editorCanvas.getObjects().filter((object)=>object.data?.role==='student_name').forEach((object)=>editorCanvas.remove(object));addText('اسم الطالب',{role:'student_name'});});document.getElementById('delete-object').addEventListener('click',()=>{const object=editorCanvas.getActiveObject();if(object){editorCanvas.remove(object);editorCanvas.discardActiveObject();editorCanvas.renderAll();}});const selected=()=>editorCanvas.getActiveObject();const syncControls=()=>{const object=selected();if(object){document.getElementById('object-text').value=object.text||'';document.getElementById('object-size').value=object.fontSize||32;document.getElementById('object-color').value=object.fill||'#062c26';document.getElementById('object-weight').value=object.fontWeight||'400';}};editorCanvas.on('selection:created',syncControls);editorCanvas.on('selection:updated',syncControls);document.getElementById('object-text').addEventListener('input',(event)=>{if(selected()){selected().set('text',event.target.value);editorCanvas.renderAll();}});document.getElementById('object-size').addEventListener('input',(event)=>{if(selected()){selected().set('fontSize',Number(event.target.value));editorCanvas.renderAll();}});document.getElementById('object-color').addEventListener('input',(event)=>{if(selected()){selected().set('fill',event.target.value);editorCanvas.renderAll();}});document.getElementById('object-weight').addEventListener('change',(event)=>{if(selected()){selected().set('fontWeight',event.target.value);editorCanvas.renderAll();}});document.getElementById('template-form').addEventListener('submit',(event)=>{const hasName=editorCanvas.getObjects().some((object)=>object.data?.role==='student_name');if(!hasName){event.preventDefault();Swal.fire({icon:'warning',title:'موضع اسم الطالب مطلوب',text:'أضف عنصر اسم الطالب قبل حفظ القالب.',confirmButtonText:'فهمت'});return;}document.getElementById('canvas-json').value=JSON.stringify(editorCanvas.toJSON(['data']));});
</script>
<script>
(() => {
    const stage = document.getElementById('canvas-stage');
    const wrap = document.getElementById('canvas-wrap');
    if (!stage || !wrap || typeof editorCanvas === 'undefined') return;

    const originalSetDimensions = editorCanvas.setDimensions.bind(editorCanvas);
    const fitCanvas = () => {
        const width = editorCanvas.getWidth();
        const height = editorCanvas.getHeight();
        if (!width || !height || !editorCanvas.wrapperEl) return;
        const availableWidth = Math.max(240, stage.clientWidth - 32);
        const scale = Math.min(1, availableWidth / width, 720 / height);
        wrap.style.width = `${Math.round(width * scale)}px`;
        wrap.style.height = `${Math.round(height * scale)}px`;
        editorCanvas.wrapperEl.style.transform = `scale(${scale})`;
        editorCanvas.wrapperEl.style.transformOrigin = 'top left';
        editorCanvas.wrapperEl.dataset.previewScale = scale;
    };

    editorCanvas.setDimensions = (...args) => {
        const result = originalSetDimensions(...args);
        requestAnimationFrame(fitCanvas);
        return result;
    };

    new ResizeObserver(fitCanvas).observe(stage);
    window.addEventListener('resize', fitCanvas);
    requestAnimationFrame(fitCanvas);
})();
</script>
