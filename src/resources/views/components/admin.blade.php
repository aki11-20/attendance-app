<header class="header">
    <div class="header_logo">
        <img class="logo" alt="COACHTECH" src="{{ asset('img/logo.png') }}">
    </div>

    <nav class="header_nav">
        <a href="/admin/attendance/list">勤怠一覧</a>
        <a href="/admin/staff/list">スタッフ一覧</a>
        <a href="/stamp_correction_request/list">申請一覧</a>

        <form action="/admin/logout" method="POST">
            @csrf
            <button>ログアウト</button>
        </form>
    </nav>
</header>
