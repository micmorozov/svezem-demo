#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 96 - Аренда погрузчика

Transp -> AnyWord<kwtype="аренда">;
What -> "автопогрузчик" | "погрузчик";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_96);