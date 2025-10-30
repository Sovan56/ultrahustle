{{-- resources/views/UserAdmin/forum/edit.blade.php --}}
@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .uh-edit { --b:#1a1a1a; --card:#0b0b0b; --muted:#a6a6a6; --neon:#CEFF1B; }
  .uh-edit .card { background:#0b0b0b; border:1px solid var(--b)!important; }
  .uh-edit label { color:#e6e6e6; font-weight:600; }
  .help { color:#a6a6a6; font-size:12px; }
  .btn-ghost { background:#101010; border:1px solid var(--b); color:#fff; }
  .btn-ghost:hover { box-shadow:0 0 0 2px var(--neon); color:#000; background:var(--neon); border-color:transparent; }
  .poll-opt-row { display:flex; gap:8px; margin-bottom:8px; }
  .poll-opt-row input { flex:1; }
</style>

<div class="main-content uh-edit">
  <section class="section">
    <div class="section-header">
      <h1 class="m-0">Edit Thread #{{ $thread->id }}</h1>
      <div class="ml-auto">
        <a class="btn btn-ghost" href="{{ route('admin.forum.page') }}">← Back</a>
        <a class="btn btn-primary" href="{{ route('forum.show',$thread->id) }}" target="_blank">View Public</a>
      </div>
    </div>

    <div class="section-body">
      <form method="POST" action="{{ route('admin.forum.update',$thread->id) }}" id="editForm">
        @csrf

        <div class="row">
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header"><h4 class="m-0">Content</h4></div>
              <div class="card-body">
                <div class="form-group">
                  <label>Title</label>
                  <input type="text" name="title" class="form-control" value="{{ old('title',$thread->title) }}" required>
                </div>

                {{-- TEXT / IMAGE / VIDEO caption --}}
                <div class="form-group" id="grp-body" @if($postType==='poll') style="display:none" @endif>
                  <label>Body</label>
                  <textarea name="body_html" class="form-control" rows="6">{{ old('body_html',$thread->body_html) }}</textarea>
                  <div class="help">Simple HTML allowed.</div>
                </div>

                {{-- IMAGE --}}
                @if($postType==='image')
                  <div class="form-group">
                    <label>Image</label>
                    <div class="mb-2">
                      @if($thread->media_url)
                        <img src="{{ $thread->media_url }}" alt="" style="max-width:100%;border:1px solid #1a1a1a;border-radius:8px">
                      @endif
                    </div>
                    <div class="input-group">
                      <input type="text" name="media_url" id="media_url" class="form-control" value="{{ old('media_url',$thread->media_url) }}" placeholder="Image URL">
                      <div class="input-group-append">
                        <button class="btn btn-ghost" type="button" id="btnUploadImg">Upload</button>
                      </div>
                    </div>
                    <input type="text" name="media_alt" class="form-control mt-2" value="{{ old('media_alt',$thread->media_alt) }}" placeholder="Alt text (optional)">
                    <div class="help">Use Upload to store on server, or paste a URL.</div>
                  </div>
                @endif

                {{-- VIDEO --}}
                @if($postType==='video')
                  <div class="form-group">
                    <label>Video</label>
                    <div class="mb-2">
                      @if($thread->media_url)
                        <video src="{{ $thread->media_url }}" controls style="max-width:100%;border:1px solid #1a1a1a;border-radius:8px" @if($thread->media_poster) poster="{{ $thread->media_poster }}" @endif></video>
                      @endif
                    </div>
                    <div class="input-group mb-2">
                      <input type="text" name="media_url" id="media_url" class="form-control" value="{{ old('media_url',$thread->media_url) }}" placeholder="Video URL">
                      <div class="input-group-append">
                        <button class="btn btn-ghost" type="button" id="btnUploadVideo">Upload</button>
                      </div>
                    </div>
                    <input type="text" name="media_poster" id="media_poster" class="form-control mb-2" value="{{ old('media_poster',$thread->media_poster) }}" placeholder="Poster URL (optional)">
                    <input type="text" name="media_alt" class="form-control" value="{{ old('media_alt',$thread->media_alt) }}" placeholder="Alt text (optional)">
                    <div class="help">Upload supports MP4/WEBM/OGG/QuickTime. Poster is optional.</div>
                  </div>
                @endif

                {{-- POLL --}}
                @if($postType==='poll')
                  <div class="form-group">
                    <label>Poll</label>
                    <div class="custom-control custom-checkbox mb-2">
                      <input type="checkbox" class="custom-control-input" id="poll_multiple" name="poll_multiple" value="1" {{ old('poll_multiple',optional($thread->poll)->multiple) ? 'checked' : '' }}>
                      <label class="custom-control-label" for="poll_multiple">Allow multiple selections</label>
                    </div>

                    <div id="pollOptions">
                      @php
                        $opts = old('poll_options', optional($thread->poll)->options?->pluck('label')->toArray() ?? []);
                        if (count($opts) < 2) $opts = array_pad($opts, 2, '');
                      @endphp
                      @foreach($opts as $i=>$label)
                        <div class="poll-opt-row">
                          <input type="text" name="poll_options[]" class="form-control" value="{{ $label }}" placeholder="Option {{ $i+1 }}" required>
                          @if($i>1)
                            <button class="btn btn-danger" type="button" onclick="this.parentNode.remove()">Remove</button>
                          @endif
                        </div>
                      @endforeach
                    </div>
                    <button type="button" class="btn btn-ghost" id="addOpt">+ Add option</button>
                    <div class="help">2–12 options.</div>
                  </div>
                @endif
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card">
              <div class="card-header"><h4 class="m-0">Meta</h4></div>
              <div class="card-body">
                <div class="form-group">
                  <label>Category</label>
                  <select name="category_id" class="form-control">
                    <option value="">— None —</option>
                    @foreach($categories as $c)
                      <option value="{{ $c->id }}" {{ (int)$thread->category_id===(int)$c->id ? 'selected':'' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <button class="btn btn-primary btn-block">Save Changes</button>
                <a href="{{ route('admin.forum.page') }}" class="btn btn-ghost btn-block">Cancel</a>
              </div>
            </div>
          </div>
        </div> {{-- row --}}
      </form>
    </div>
  </section>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
function uploadAndFill(btn, acceptVideo=false){
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = acceptVideo ? 'video/*' : 'image/*';
  input.onchange = async ()=>{
    if(!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('file', input.files[0]);
    try{
      const r = await fetch('{{ url('/forum/upload') }}', {
        method:'POST',
        headers: {'X-CSRF-TOKEN': CSRF},
        body: fd
      });
      const j = await r.json();
      if(!r.ok){ alert((j?.message)||'Upload failed'); return; }
      document.getElementById('media_url').value = j.url;
      if (acceptVideo && j.type==='video') {
        // poster left for manual or separate upload (optional)
      }
    }catch(e){ alert('Upload failed'); }
  };
  input.click();
}

document.getElementById('btnUploadImg')?.addEventListener('click', ()=> uploadAndFill(null,false));
document.getElementById('btnUploadVideo')?.addEventListener('click', ()=> uploadAndFill(null,true));

document.getElementById('addOpt')?.addEventListener('click', ()=>{
  const host = document.getElementById('pollOptions');
  const count = host.querySelectorAll('.poll-opt-row').length;
  if (count >= 12) return;
  const div = document.createElement('div');
  div.className = 'poll-opt-row';
  div.innerHTML = `
    <input type="text" name="poll_options[]" class="form-control" placeholder="Option ${count+1}" required>
    <button class="btn btn-danger" type="button" onclick="this.parentNode.remove()">Remove</button>
  `;
  host.appendChild(div);
});
</script>

@include('UserAdmin.common.footer')
