<x-flatpack-layout>
<div class="py-20">
    <div class="flex flex-col items-start justify-center max-w-5xl gap-5 p-10 mx-auto bg-no-repeat bg-contain lg:flex-row box">
        <div class="pt-6"><img src="{{ asset('flatpack/images/empty-home.svg') }}" alt="" class="max-w-sm"></div>
        <div class="flex flex-col items-center justify-start gap-6 m-6">
            <div class="block w-full prose text-left">
                <h2 class="text-gray-600">Oh no, your dashboard is empty!</h2>
                <p class="text-sm text-gray-600">You can quickly fix this by creating a block definition in your flatpack files. Simply follow the steps below.</p>
                <hr />
                <h3 class="text-gray-600">Create a new block:</h3>
                <ol class="text-sm text-gray-700">
                    <li>
                        <span>📦</span>
                        <span>Create a new file under <strong>/{{ (config('flatpack.directory', 'flatpack')) }}</strong> directory:</span>
                        <span class="px-2 py-1 font-mono text-xs bg-gray-300 rounded">home.yaml</span>
                    </li>
                    <li>
                        <span>👷</span>
                        <span>Copy, paste and save the following template.</span>
                        <pre class="p-2 my-3 font-mono text-xs text-gray-600 bg-gray-300 rounded">
# =======================================
# Home Blocks Definition
# =======================================

blocks:
  posts:
    label: Posts
    type: totals
    value: count

</pre>
                    </li>
                     <li>
                        <span>🎉</span>
                        <span class="font-bold">Done!</span>
                        <span>Refresh this page.</span>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>
</x-flatpack-layout>
