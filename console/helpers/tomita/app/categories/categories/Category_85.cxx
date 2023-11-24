#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 85 - Автомобильная перевозка

Fact -> "автотранспорт" | "автомобильный";

S -> Fact interp(Category.Category_85);
