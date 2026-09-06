<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DLHS :: Home</title>
<link rel="icon" type="image/png" href="images/dlhslogo2.jpg"/>
<link rel="stylesheet" href="bootstrap/bootstrap.min.css">

<link href="adminLogin/fonts/font-awesome-4.7.0/css/font-awesome.css" rel="stylesheet">
<style type="text/css">
		:root {
			--ink: #122033;
			--muted: #65758b;
			--navy: #071d3b;
			--blue: #1d5fbf;
			--teal: #0f9f8f;
			--line: #dbe4ef;
			--soft: #f5f8fc;
		}
		* {
			box-sizing: border-box;
		}
		body {
			min-height: 100vh;
			margin: 0;
			color: var(--ink);
			background: #eef3f9;
			font-family: "Inter", "Roboto", Arial, sans-serif;
		}
		.login-shell {
			min-height: 100vh;
			display: grid;
			grid-template-columns: minmax(380px, 0.95fr) minmax(420px, 1.05fr);
		}
		.brand-panel {
			position: relative;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			padding: 58px;
			color: #fff;
			background:
				linear-gradient(rgba(7, 29, 59, 0.90), rgba(7, 29, 59, 0.82)),
				url("images/dlhslogo3.jpg") center/cover no-repeat;
			overflow: hidden;
		}
		.brand-panel:after {
			content: "";
			position: absolute;
			inset: 0;
			background: linear-gradient(135deg, rgba(15, 159, 143, 0.20), rgba(29, 95, 191, 0.18));
			pointer-events: none;
		}
		.brand-content,
		.brand-footer {
			position: relative;
			z-index: 1;
		}
		.brand-logo {
			width: 94px;
			height: 94px;
			object-fit: contain;
			background: #fff;
			border-radius: 8px;
			padding: 10px;
			margin-bottom: 34px;
			box-shadow: 0 16px 40px rgba(0, 0, 0, 0.20);
		}
		.brand-eyebrow {
			margin: 0 0 12px;
			color: #a9e8df;
			font-size: 13px;
			font-weight: 700;
			letter-spacing: 1.4px;
			text-transform: uppercase;
		}
		.brand-title {
			max-width: 620px;
			margin: 0;
			font-size: 44px;
			line-height: 1.08;
			font-weight: 800;
		}
		.brand-copy {
			max-width: 540px;
			margin: 22px 0 0;
			color: #d7e5f5;
			font-size: 17px;
			line-height: 1.7;
		}
		.brand-stats {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 14px;
			margin-top: 42px;
			max-width: 620px;
		}
		.brand-stat {
			border: 1px solid rgba(255, 255, 255, 0.20);
			border-radius: 8px;
			padding: 16px;
			background: rgba(255, 255, 255, 0.08);
		}
		.brand-stat strong {
			display: block;
			font-size: 22px;
			line-height: 1;
		}
		.brand-stat span {
			display: block;
			margin-top: 8px;
			color: #c8d7e8;
			font-size: 12px;
			text-transform: uppercase;
		}
		.brand-footer {
			color: #c8d7e8;
			font-size: 13px;
		}
		.brand-footer a {
			color: #fff;
			font-weight: 700;
			text-decoration: none;
		}
		.access-panel {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 48px;
			background: #f7faff;
		}
		.access-card {
			width: 100%;
			max-width: 520px;
			padding: 42px;
			border: 1px solid var(--line);
			border-radius: 8px;
			background: #fff;
			box-shadow: 0 24px 70px rgba(34, 57, 85, 0.12);
		}
		.mobile-logo {
			display: none;
			width: 72px;
			height: 72px;
			object-fit: contain;
			margin-bottom: 22px;
		}
		.access-kicker {
			margin: 0 0 10px;
			color: var(--teal);
			font-size: 13px;
			font-weight: 800;
			letter-spacing: 1.2px;
			text-transform: uppercase;
		}
		.access-card h2 {
			margin: 0;
			color: var(--ink);
			font-size: 30px;
			font-weight: 800;
		}
		.access-card p {
			margin: 13px 0 28px;
			color: var(--muted);
			line-height: 1.65;
		}
		.role-list {
			display: grid;
			gap: 12px;
			margin: 0 0 28px;
		}
		.role-link {
			display: flex;
			align-items: center;
			gap: 15px;
			min-height: 74px;
			padding: 16px 18px;
			border: 1px solid var(--line);
			border-radius: 8px;
			color: var(--ink);
			text-decoration: none;
			background: #fff;
			transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
		}
		.role-link:hover,
		.role-link:focus {
			color: var(--ink);
			text-decoration: none;
			border-color: rgba(29, 95, 191, 0.45);
			box-shadow: 0 12px 28px rgba(29, 95, 191, 0.12);
			transform: translateY(-1px);
		}
		.role-icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 44px;
			height: 44px;
			flex: 0 0 44px;
			border-radius: 8px;
			color: #fff;
			background: var(--blue);
		}
		.role-link:nth-child(2) .role-icon {
			background: var(--teal);
		}
		.role-link:nth-child(3) .role-icon {
			background: #253858;
		}
		.role-text {
			flex: 1;
		}
		.role-text strong,
		.role-text span {
			display: block;
		}
		.role-text strong {
			font-size: 16px;
			margin-bottom: 4px;
		}
		.role-text span {
			color: var(--muted);
			font-size: 13px;
		}
		.role-arrow {
			color: #9aa9bb;
		}
		.access-note {
			padding-top: 22px;
			border-top: 1px solid var(--line);
			color: var(--muted);
			font-size: 13px;
			line-height: 1.6;
		}
		.access-note strong {
			color: var(--ink);
		}
		@media (max-width: 900px) {
			.login-shell {
				grid-template-columns: 1fr;
			}
			.brand-panel {
				min-height: 360px;
				padding: 36px 28px;
			}
			.brand-title {
				font-size: 34px;
			}
			.brand-stats {
				grid-template-columns: 1fr;
			}
			.access-panel {
				padding: 28px 18px 42px;
			}
		}
		@media (max-width: 560px) {
			.brand-panel {
				display: none;
			}
			.access-panel {
				min-height: 100vh;
				align-items: flex-start;
				padding-top: 34px;
			}
			.access-card {
				padding: 28px 20px;
			}
			.mobile-logo {
				display: block;
			}
			.access-card h2 {
				font-size: 26px;
			}
		}
	</style>

<script src="jQuery3.3.1.js"></script>

</head>
<body>
<main class="login-shell">
	<section class="brand-panel" aria-label="DLHS school management portal">
		<div class="brand-content">
			<img src="images/dlhslogo.png" class="brand-logo" alt="DLHS logo">
			<p class="brand-eyebrow">Deeper Life High School, Kaduna Campus</p>
			<h1 class="brand-title">Learning Management System</h1>
			<p class="brand-copy">A focused portal for academic operations, staff workflows, and student assessment access.</p>
			<div class="brand-stats" aria-label="Portal highlights">
				<div class="brand-stat">
					<strong>01</strong>
					<span>Secure access</span>
				</div>
				<div class="brand-stat">
					<strong>03</strong>
					<span>User portals</span>
				</div>
				<div class="brand-stat">
					<strong>24/7</strong>
					<span>Availability</span>
				</div>
			</div>
		</div>
		<div class="brand-footer">Powered by <a href="https://jlm.com.ng" target="_blank" rel="noopener">JLM</a></div>
	</section>
	<section class="access-panel">
		<div class="access-card">
			<img src="images/dlhslogo.png" class="mobile-logo" alt="DLHS logo">
			<p class="access-kicker">Welcome back</p>
			<h2>Choose your portal</h2>
			<p>Select the access area that matches your role to continue into the DLHS management system.</p>
			<nav class="role-list" aria-label="User type">
				<a class="role-link" href="adminLogin">
					<span class="role-icon"><i class="fa fa-user"></i></span>
					<span class="role-text">
						<strong>Admin</strong>
						<span>Manage school setup, records, tests, and reporting.</span>
					</span>
					<i class="fa fa-chevron-right role-arrow" aria-hidden="true"></i>
				</a>
				<a class="role-link" href="staffLogin">
					<span class="role-icon"><i class="fa fa-user-circle"></i></span>
					<span class="role-text">
						<strong>Staff</strong>
						<span>Prepare lessons, questions, scores, and class activity.</span>
					</span>
					<i class="fa fa-chevron-right role-arrow" aria-hidden="true"></i>
				</a>
				<a class="role-link" href="studentLogin">
					<span class="role-icon"><i class="fa fa-group"></i></span>
					<span class="role-text">
						<strong>Student</strong>
						<span>Access tests, assignments, results, and learning tools.</span>
					</span>
					<i class="fa fa-chevron-right role-arrow" aria-hidden="true"></i>
				</a>
			</nav>
			<div class="access-note"><strong>Need access?</strong> Contact the school administrator for your login details.</div>
		</div>
	</section>
</main>
</body>
</html>                            
