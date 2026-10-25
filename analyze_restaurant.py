from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt
# from sklearn.compose import ColumnTransformer
# from sklearn.preprocessing import OneHotEncoder
# from sklearn.pipeline import make_pipeline
# from sklearn.ensemble import RandomForestRegressor
# from sklearn.dummy import DummyRegressor
# from sklearn.metrics import mean_absolute_error


data_dir = Path("storage/app/ai-training")

orders = pd.read_csv(data_dir / "orders.csv")
items = pd.read_csv(data_dir / "order_items.csv")

# تحويل العمود إلى تاريخ ووقت
orders["created_at"] = pd.to_datetime(
    orders["created_at"],
    errors="coerce"
)

# استخراج التاريخ فقط
orders["order_date"] = orders["created_at"].dt.date

daily_sales = orders.groupby("order_date").agg(
    order_count=('id' , 'size'),
    total_sales=('final_amount','sum'),
    avg_orders=('final_amount','mean')
)

print(daily_sales.round(2).to_string())


# print(orders.shape)
# print(orders.columns.to_list)
# print(orders.head(6))

# orders.info()

# print(orders.isna().sum())
# print("\n ----Data _____")
# orders['created_at'] = pd.to_datetime(orders["created_at"] , errors="coerce")
# print("\n __ Data type : ", orders["created_at"].dtype)

# print("Data Missing : " , orders["created_at"].isna().sum())


# print("Earliest order:", orders["created_at"].min())
# print("Latest order:", orders["created_at"].max())


# print("\n--- Daily order counts ---")
# orders["order_date"] = orders["created_at"].dt.date

# daily_orders = orders.groupby("order_date").size()

# print(daily_orders.to_string)


# print("\n--- Daily sales report ---")

# orders["order_date"] = orders["created_at"].dt.date

# daily_sales = orders.groupby("order_date").agg(
#     orders_count=("id", "size"),
#     total_sales=("final_amount", "sum"),
#     average_order=("final_amount", "mean")
# )

# print(daily_sales.round(2).to_string())

# print("\n--- Best sales day ---")

# best_day = daily_sales["total_sales"].idxmax()
# best_sales = daily_sales.loc[best_day, "total_sales"]

# print("Date:", best_day)
# print("Total sales:", round(best_sales, 2))

# print("\n--- Data quality check ---")

# print("Unique order IDs:", orders["id"].nunique())
# print("Duplicate order IDs:", orders["id"].duplicated().sum())
# print("Negative final amounts:",
#       (orders["final_amount"] < 0).sum())
# print("Zero final amounts:",
#       (orders["final_amount"] == 0).sum())

# print("\n--- Largest orders ---")
# print(
#     orders.nlargest(10, "final_amount")[
#         ["id", "created_at", "status", "final_amount"]
#     ].to_string(index=False)
# )





# print("\n--- My first Machine Learning model ---")

# # 1. Prepare features
# ml_data = orders.sort_values("created_at").copy()

# ml_data["hour"] = ml_data["created_at"].dt.hour
# ml_data["day_of_week"] = ml_data["created_at"].dt.dayofweek

# features = ["hour", "day_of_week", "delivery_type"]
# X = ml_data[features]
# y = ml_data["final_amount"]

# # 2. Split by time: older orders train, newer orders test
# split_point = int(len(ml_data) * 0.8)

# X_train = X.iloc[:split_point]
# X_test = X.iloc[split_point:]
# y_train = y.iloc[:split_point]
# y_test = y.iloc[split_point:]

# # 3. Convert delivery type into a numerical representation
# preprocessor = ColumnTransformer(
#     transformers=[
#         ("delivery", OneHotEncoder(handle_unknown="ignore"),
#          ["delivery_type"])
#     ],
#     remainder="passthrough"
# )

# # 4. Create and train the model
# model = make_pipeline(
#     preprocessor,
#     RandomForestRegressor(n_estimators=100, random_state=42)
# )

# model.fit(X_train, y_train)

# # 5. Make predictions
# predictions = model.predict(X_test)

# # 6. Compare against a simple baseline
# baseline = DummyRegressor(strategy="median")
# baseline.fit(X_train, y_train)
# baseline_predictions = baseline.predict(X_test)

# print("Training orders:", len(X_train))
# print("Testing orders:", len(X_test))
# print("Model MAE:", round(mean_absolute_error(y_test, predictions), 2))
# print("Baseline MAE:", round(
#     mean_absolute_error(y_test, baseline_predictions), 2
# ))

# print("\n--- Actual vs predicted ---")
# comparison = pd.DataFrame({
#     "actual": y_test.to_numpy(),
#     "predicted": predictions.round(2)
# })
# print(comparison.head(10).to_string(index=False))


# # # تحويل الأعمدة إلى أنواع مناسبة للتحليل
# # orders["created_at"] = pd.to_datetime(
# #     orders["created_at"], errors="coerce"
# # )
# # items["quantity"] = pd.to_numeric(
# #     items["quantity"], errors="coerce"
# # )
# # items["total"] = pd.to_numeric(
# #     items["total"], errors="coerce"
# # )

# # print("=== Data overview ===")
# # print("Orders:", len(orders))
# # print("Order items:", len(items))
# # print("\n=== Order statuses ===")
# # print(orders["status"].value_counts(dropna=False))

# # print("\n=== Top 10 products by quantity ===")
# # products = (
# #     items.groupby("product_name", dropna=False)
# #     .agg(
# #         quantity_sold=("quantity", "sum"),
# #         line_total=("total", "sum"),
# #     )
# #     .sort_values("quantity_sold", ascending=False)
# # )

# # print(products.head(10).to_string())

# # # تحليل عدد الطلبات حسب ساعة الإنشاء
# # orders["hour"] = orders["created_at"].dt.hour
# # hourly = orders.groupby("hour").size()

# # print("\n=== Orders by hour ===")
# # print(hourly.to_string())

# # hourly.plot(kind="bar", title="Orders by Hour")
# # plt.xlabel("Hour of day")
# # plt.ylabel("Number of orders")
# # plt.tight_layout()
# # plt.show()


# # # Save the product analysis as a CSV report
# # products.to_csv(
# #     data_dir / "top_products_report.csv",
# #     encoding="utf-8-sig"
# # )

# # # Create a clearer chart for the top 10 products
# # top_products = products.head(10).sort_values("quantity_sold")

# # top_products["quantity_sold"].plot(
# #     kind="barh",
# #     figsize=(10, 6),
# #     title="Top Products by Quantity Sold"
# # )

# # plt.xlabel("Quantity Sold")
# # plt.ylabel("Product")
# # plt.tight_layout()

# # # Save the chart instead of only showing a window
# # plt.savefig(data_dir / "top_products_chart.png", dpi=150)
# # plt.show()

# # print("\nReports saved:")
# # print(data_dir / "top_products_report.csv")
# # print(data_dir / "top_products_chart.png")
