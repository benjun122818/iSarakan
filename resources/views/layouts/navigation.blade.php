<!-- <nav x-data="{ open: false }" class="bg-white border-b border-gray-100"> 
  
    <div class="navbar bg-base-100 overflow-visible">
        <div class="w-24 rounded-full pl-8 ml-20">
            <img src="/img/mmsubgsides.png" />
        </div>
        <div class="flex-1">
            <a class="link link-hover normal-case text-xl">MARIANO MARCOS STATE UNIVERSITY</a>
        </div>
        <div class="hidden sm:flex sm:items-center sm:ml-6">

            <div class="dropdown dropdown-bottom dropdown-end">
                <label tabindex="0" class="btn m-1 btn-ghost">{{ Auth::user()->name }}</label>
                <ul tabindex="0" class="dropdown-content z-50 menu p-2 shadow bg-base-100 rounded-box w-52">
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __("Log Out") }}
                            </x-dropdown-link>
                        </form>
                    </li>

                </ul>
            </div>

        </div>

    </div>

</nav>-->

<nav class="bg-gray-800 p-6">

 <div class="flex items-center justify-between">
 <div class="w-24 rounded-full pl-8 ml-20">
            <img src="/img/mmsubgsides.png" />
        </div>
       <div class="flex-1">
            <a class="text-white link link-hover normal-case text-xl">MARIANO MARCOS STATE UNIVERSITY</a>
        </div>

    <div class="flex items-center space-x-4">

    
  <div class="hidden sm:flex sm:items-center sm:ml-6">

            <div class="dropdown dropdown-bottom dropdown-end">
                <label tabindex="0" class="text-white btn m-1 btn-ghost">{{ Auth::user()->name }}</label>
                <ul tabindex="0" class="dropdown-content z-50 menu p-2 shadow bg-base-100 rounded-box w-52">
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __("Log Out") }}
                            </x-dropdown-link>
                        </form>
                    </li>

                </ul>
            </div>

        </div>
    </div>

 </div>

</nav>