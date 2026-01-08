<?php
/**
 * User Management Component
 * Educational Security Scanner Dashboard
 */
?>

<div class="bg-white rounded-lg shadow p-6" x-data="{
    users: [], 
    loading: true, 
    showAddUser: false, 
    showEditUser: false, 
    editingUser: null,
    newUser: { username: '', email: '', password: '', role: 'user' },
    
    loadUsers() {
        this.loading = true;
        fetch('api/admin_get_users.php')
            .then(response => response.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.users = data.users;
                } else {
                    alert('Error loading users: ' + data.error);
                }
            })
            .catch(error => {
                this.loading = false;
                alert('Error: ' + error.message);
            });
    },
    
    addUser() {
        fetch('api/admin_add_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(this.newUser)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAddUser = false;
                this.newUser = { username: '', email: '', password: '', role: 'user' };
                this.loadUsers();
                alert('User added successfully');
            } else {
                alert('Error adding user: ' + data.error);
            }
        });
    },
    
    editUser(user) {
        this.editingUser = { ...user, new_password: '' };
        this.showEditUser = true;
    },
    
    updateUser() {
        fetch('api/admin_update_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(this.editingUser)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showEditUser = false;
                this.editingUser = null;
                this.loadUsers();
                alert('User updated successfully');
            } else {
                alert('Error updating user: ' + data.error);
            }
        });
    },
    
    deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This will also delete all their scan history.')) {
            fetch('api/admin_delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'user_id=' + userId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.loadUsers();
                    alert('User deleted successfully');
                } else {
                    alert('Error deleting user: ' + data.error);
                }
            });
        }
    }
}" x-init="loadUsers()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">User Management</h2>
            <p class="text-sm text-gray-600">Manage system users and their permissions</p>
        </div>
        <div class="flex space-x-2">
            <button @click="showAddUser = true" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Add User
            </button>
            <button @click="loadUsers()" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Refresh
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-8">
        <svg class="animate-spin mx-auto h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-500">Loading users...</p>
    </div>

    <!-- Users Table -->
    <div x-show="!loading" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="user in users" :key="user.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="user.id"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="user.username"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="user.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'" 
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" x-text="user.role"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="user.created_at"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button @click="editUser(user)" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button x-show="user.username !== 'admin'" @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Add User Modal -->
    <div x-show="showAddUser" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New User</h3>
                <form @submit.prevent="addUser()">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" x-model="newUser.username" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <!-- added missing email field -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" x-model="newUser.email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" x-model="newUser.password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select x-model="newUser.role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showAddUser = false; newUser = { username: '', email: '', password: '', role: 'user' }" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div x-show="showEditUser" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit User</h3>
                <form @submit.prevent="updateUser()">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" x-model="editingUser.username" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password (leave blank to keep current)</label>
                        <input type="password" x-model="editingUser.new_password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select x-model="editingUser.role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showEditUser = false; editingUser = null" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
