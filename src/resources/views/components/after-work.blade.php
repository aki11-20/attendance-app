<header class="header">
    <div class="header_logo">
        <img class="logo" alt="COACHTECH" src="{{ asset('img/logo.png') }}">
    </div>

    <nav class="header_nav">
        <a href="/attendance/list">今月の出勤一覧</a>
        <a href="/stamp_correction_request/list">申請一覧</a>

        <form action="/logout" method="POST">
            @csrf
            <button>ログアウト</button>
        </form>
    </nav>
</header>
