document.addEventListener("turbolinks:before-cache", function() {
    var flash = document.getElementById("flash");
    if (flash) {
        flash.innerHTML = "";
    }
});

window.showUserFlash = function(message, isError) {
    var flash = document.getElementById("flash");
    if (!flash) {
        return;
    }

    flash.textContent = "";
    var paragraph = document.createElement("p");
    paragraph.className = (isError ? "error" : "success") + " p-0.5";
    paragraph.textContent = message;
    flash.appendChild(paragraph);
};

window.sendUserRequest = async function(method, url, data) {
    var options = { method: method };
    if (data) {
        options.headers = { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" };
        options.body = new URLSearchParams(data).toString();
    }

    var response = await fetch(url, options);
    var text = await response.text();
    var result;
    try {
        result = JSON.parse(text);
    } catch (error) {
        throw new Error(response.ok ? "The server returned an invalid response." : (text || "Request failed."));
    }

    if (!response.ok) {
        throw new Error(result.msg || text || "Request failed.");
    }
    return result;
};

window.adminUsers = function() {
    return {
        newUser: { username: "", password: "" },
        show: false,
        userId: null,

        root: function() {
            var form = document.getElementById("newuserform");
            return form ? (form.getAttribute("data-proot") || "") : "";
        },

        appendUser: function(user) {
            var template = document.getElementById("user-row-template");
            var userList = document.getElementById("users");
            if (!template || !template.content.firstElementChild || !userList) {
                throw new Error("Unable to display the new user.");
            }

            var row = template.content.firstElementChild.cloneNode(true);
            var userId = String(user.id);
            var userUrl = this.root() + "/admin/users/" + encodeURIComponent(userId) + "/";
            row.setAttribute("data-user", userId);
            row.querySelector("[data-user-link]").setAttribute("href", userUrl);
            row.querySelector("[data-user-edit]").setAttribute("href", userUrl);
            row.querySelector("[data-user-name]").textContent = user.username;
            row.querySelector("[data-user-delete]").addEventListener("click", (event) => {
                event.preventDefault();
                this.open(userId);
            });
            userList.appendChild(row);
        },

        addUser: async function() {
            try {
                var response = await window.sendUserRequest(
                    "POST", this.root() + "/admin/users/", this.newUser
                );
                if (!response.user) {
                    throw new Error("The server response did not include the new user.");
                }
                this.appendUser(response.user);
                this.newUser = { username: "", password: "" };
                window.showUserFlash(response.msg || "User added.", false);
            } catch (error) {
                window.showUserFlash(error.message || "Error adding user.", true);
            }
        },

        open: function(userId) {
            this.userId = userId;
            this.show = true;
        },

        close: function() {
            this.show = false;
            this.userId = null;
        },

        deleteUser: async function() {
            if (!this.userId) {
                window.showUserFlash("Select a user to delete.", true);
                return;
            }

            try {
                var response = await window.sendUserRequest(
                    "DELETE",
                    this.root() + "/admin/users/" + encodeURIComponent(this.userId) + "/"
                );
                var rows = document.querySelectorAll("#users li[data-user]");
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].getAttribute("data-user") === String(this.userId)) {
                        rows[i].parentNode.removeChild(rows[i]);
                        break;
                    }
                }
                this.close();
                window.showUserFlash(response.msg || "User deleted.", false);
            } catch (error) {
                window.showUserFlash(error.message || "Error deleting user.", true);
            }
        }
    };
};

window.adminUserEdit = function() {
    return {
        save: async function() {
            var form = document.getElementById("userform");
            var root = form.getAttribute("data-proot") || "";
            var userId = form.getAttribute("data-user");

            try {
                var response = await window.sendUserRequest(
                    "PUT", root + "/admin/users/" + encodeURIComponent(userId) + "/", {
                        username: document.getElementById("edituser_name").value,
                        password: document.getElementById("edituser_password").value,
                        languages: document.getElementById("edituser_languages").value,
                        tags: document.getElementById("edituser_tags").value,
                        role: document.getElementById("edituser_role").value
                    }
                );
                window.showUserFlash(response.msg || "Changes saved.", false);
            } catch (error) {
                window.showUserFlash(error.message || "Error updating user.", true);
            }
        }
    };
};
