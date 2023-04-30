<div class="mx-2 mt-0 mb-1">
    <div class="mb-1">
        <div class="flex space-x-1">
            <span class="text-left text-indigo-500">Flatpack template files for</span>
            <span class="text-left font-bold text-indigo-500">{{ $model }}</span>
            <span class="flex-1 content-repeat-[.] text-gray"></span>
            <span class="text-right text-indigo-500">Created [<b>{{ $files->count() }}</b>] files</span>
        </div>
    </div>

    @foreach($files as $name => $file)
        <div>
            <div class="flex space-x-1">
                <span class="font-bold">{{ $name }}</span>
                <span class="flex-1 content-repeat-[.] text-gray"></span>
                <span class="text-gray">Created in:</span>
                <span class="font-bold text-green">{{ $file }}</span>
            </div>
        </div>
    @endforeach
    
    <div class="mt-1">
        <div class="space-x-2">
            <span>✨ Done!</span>
        </div>
    </div>
</div>