#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 63 - Перевозка емкостей
Fact -> "уголь" | "битум" | "асбест";

S -> Fact interp(Category.Category_63);