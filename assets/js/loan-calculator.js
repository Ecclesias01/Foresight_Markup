document.addEventListener("DOMContentLoaded", () => {
  const calculateBtn = document.getElementById("calculateBtn");

  calculateBtn.addEventListener("click", calculateLoan);

  function calculateLoan() {
    // 1. Get input values
    const principal = parseFloat(document.getElementById("amount").value);
    const calculatedInterest = parseFloat(document.getElementById("interest").value) / 100 / 12; // Monthly Interest
    const calculatedPayments = parseFloat(document.getElementById("years").value) * 12; // Total Months

    // 2. Validate inputs
    if (isNaN(principal) || isNaN(calculatedInterest) || isNaN(calculatedPayments) || principal <= 0) {
      alert("Please check your numbers and fill in all fields correctly.");
      return;
    }

    // 3. Compute monthly payment formula: P * r * (1 + r)^n / ((1 + r)^n - 1)
    const x = Math.pow(1 + calculatedInterest, calculatedPayments);
    const monthly = (principal * x * calculatedInterest) / (x - 1);

    // 4. Check if result is a finite number
    if (isFinite(monthly)) {
      const total = monthly * calculatedPayments;
      const interest = total - principal;

      // 5. Output formatted currency
      document.getElementById("monthlyPayment").innerText = formatCurrency(monthly);
      document.getElementById("totalPayment").innerText = formatCurrency(total);
      document.getElementById("totalInterest").innerText = formatCurrency(interest);

      // Show results block
      document.getElementById("results").style.display = "block";
    } else {
      alert("Please check your numbers.");
    }
  }

  // Helper function to format numbers as currency
  function formatCurrency(num) {
    return "$" + num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,");
  }
});