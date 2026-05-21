const usernameInput = document.querySelector("#username");

usernameInput.addEventListener("blur", function () {
	const username = this.value.trim();

	if (username.length === 0) {
		return;
	}

	fetch(`api/auth/check-username?username=${encodeURIComponent(username)}`, {
		method: "GET",
		headers: {
			"Accept": "application/json",
		},
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.exists === true) {
				document.querySelector("#usernameFeedback").textContent =
					"Username already exists.";
			} else {
				document.querySelector("#usernameFeedback").textContent = "";
			}
		})
		.catch((error) => {
			console.error("Error checking username:", error);
		});
});
