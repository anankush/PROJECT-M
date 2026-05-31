

const DataStore = {
    state: {
        categories: [],
        totalBudget: 0,
        totalExpenditure: 0,
        currentMonth: '',
        currentCategoryId: null,
        userCurrency: '₹',
        email: ''
    },

    listeners: [],
    subscribe(callback) {
        this.listeners.push(callback);
    },
    notify() {
        this.listeners.forEach(callback => callback(this.state));
    },
    set(key, value) {
        this.state[key] = value;
        this.notify();
    },
    get(key) {
        return this.state[key];
    },
    async fetchAllData(monthVal = null) {
        try {
            if (!monthVal) {
                const now = new Date();
                monthVal = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            }
            this.state.currentMonth = monthVal;
            const catRes = await fetch(`${API_URL}?action=get_categories&month=${monthVal}`);
            const catData = await catRes.json();
            if (catData.status === 'success') {
                this.state.categories = catData.data;
                this.state.totalBudget = this.state.categories.reduce((sum, cat) => sum + (parseFloat(cat.budget) || 0), 0);
            }
            const expRes = await fetch(`${API_URL}?action=get_total_expenditure&month=${monthVal}`);
            const expData = await expRes.json();
            if (expData.status === 'success') {
                this.state.totalExpenditure = parseFloat(expData.total) || 0;
            }

            this.notify();
        } catch (error) {
            console.error("Failed to fetch unified data:", error);
        }
    }
};

window.DataStore = DataStore;
