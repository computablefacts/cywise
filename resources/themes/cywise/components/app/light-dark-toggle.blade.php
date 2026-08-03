<div 
    x-data="{
        theme: 'light',
        toggle() {
            if(this.theme == 'dark'){ 
                this.theme = 'light';
                localStorage.setItem('theme', 'light');
            }else{ 
                this.theme = 'dark';
                localStorage.setItem('theme', 'dark');
            }
        }
    }"
    x-init="
        if(localStorage.getItem('theme')){
            theme = localStorage.getItem('theme');
        }
        if(theme=='system'){
            theme =  'light';
        }
        if(document.documentElement.classList.contains('dark')){ theme='dark'; }
        $watch('theme', function(value){
            if(value == 'dark'){
                document.documentElement.classList.add('dark');
        } else {
                document.documentElement.classList.remove('dark');
            }
        })
    "
    x-on:click="toggle()"
    class="d-flex align-items-center px-2 py-1 small rounded cursor-pointer select-none hover-bg-light"
>

    <input type="hidden" name="toggleDarkMode" :value="theme">
 
    <button
        x-ref="toggle"
        type="button"
        role="switch"
        :aria-checked="theme == 'dark'"
        :aria-labelledby="$id('toggle-label')"
        :class="(theme == 'dark') ? 'bg-secondary' : 'bg-light'"
        class="position-relative d-inline-flex border-0 py-1 ms-1 transition rounded-pill" style="width: 2rem;"
    >
        <span
            :class="(theme == 'dark') ? 'translate-x-[16px]' : 'translate-x-[2px]'"
            class="transition bg-white rounded-circle shadow-sm"
            style="width: 0.75rem; height: 0.75rem;"
            aria-hidden="true"
        ></span>
    </button>
    <label
        :id="$id('toggle-label')"
        class="ms-2 fw-medium cursor-pointer mb-0"
    >
        <span x-show="(theme == 'light' || theme == null)">Dark Mode</span>
        <span x-show="(theme == 'dark')">Light Mode</span>
    </label>

</div>